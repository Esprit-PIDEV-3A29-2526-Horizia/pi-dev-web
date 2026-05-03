<?php

namespace App\Controller\Admin;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use App\Repository\VehiculeRepository;
use App\Service\ContratService;
use App\Service\EmailService;
use App\Service\GeolocationService;
use App\Service\OCRService;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

#[Route('/admin/location')]
class LocationController extends AbstractController
{
    private const EXTRAS_DISPONIBLES = [
        'siege_bebe'                => ['label' => 'Siège bébé',               'prix' => 15.0],
        'gps'                       => ['label' => 'GPS',                       'prix' => 10.0],
        'plein_carburant'           => ['label' => 'Plein carburant',           'prix' => 50.0],
        'conducteur_supplementaire' => ['label' => 'Conducteur supplémentaire', 'prix' => 20.0],
        'wifi'                      => ['label' => 'WiFi embarqué',             'prix' => 8.0],
        'assurance_complementaire'  => ['label' => 'Assurance complémentaire',  'prix' => 25.0],
    ];

    // ══════════════════════════════════════════════════════════
    // 📋 LISTE
    // ══════════════════════════════════════════════════════════
    #[Route('/', name: 'admin_location_index', methods: ['GET'])]
    public function index(LocationRepository $repository, Request $request): Response
    {
        $recherche = $request->query->get('search', '');
        $page      = max(1, (int) $request->query->get('page', 1));
        $vue       = $request->query->get('vue', 'table');
        $limit     = $vue === 'cards' ? 6 : 10;

        $tousLesResultats = !empty($recherche)
? $repository->rechercherParClient((string) $recherche)
            : $repository->findBy([], ['dateDebut' => 'DESC']);

        $total      = count($tousLesResultats);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page       = min($page, $totalPages);
        $locations  = array_slice($tousLesResultats, ($page - 1) * $limit, $limit);

        return $this->render('admin/location/index.html.twig', [
            'locations'   => $locations,
            'recherche'   => $recherche,
            'page'        => $page,
            'total_pages' => $totalPages,
            'total'       => $total,
            'limit'       => $limit,
            'vue'         => $vue,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // ➕ NOUVEAU
    // ══════════════════════════════════════════════════════════
    // ══════════════════════════════════════════════════════════
// ➕ NOUVEAU
// ══════════════════════════════════════════════════════════
#[Route('/new', name: 'admin_location_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $em,
    VehiculeRepository $vehiculeRepository,
    ContratService $contratService,
    EmailService $emailService,
    WhatsAppService $whatsAppService
): Response {
    $location = new Location();
    $form     = $this->createForm(LocationType::class, $location);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔴 Récupérer le véhicule et son prix
        $vehicule = $location->getVehicule();
        if ($vehicule) {
            // Forcer le prix par jour depuis le véhicule
            $location->setPrixParJour((string) $vehicule->getPrixParJour());
            $vehicule->setEtat('louee');
            $em->persist($vehicule);
        } else {
            $this->addFlash('error', 'Veuillez sélectionner un véhicule.');
            return $this->redirectToRoute('admin_location_new');
        }
         // ✅ FORCER le prix depuis le véhicule (sécurité)
        $prixVehicule = $vehicule->getPrixParJour();
        if ($prixVehicule === null || $prixVehicule <= 0) {
            $this->addFlash('error', 'Le véhicule sélectionné n\'a pas de prix valide.');
            return $this->redirectToRoute('admin_location_new');
        }

        // ── Extras ──
        $extrasChoisis = $request->request->all('extras');
        $extrasData    = [];
        foreach ((array) $extrasChoisis as $cle) {
            if (isset(self::EXTRAS_DISPONIBLES[$cle])) {
                $extrasData[$cle] = self::EXTRAS_DISPONIBLES[$cle]['prix'];
            }
        }
        $location->setExtras(empty($extrasData) ? null : $extrasData);
        $location->calculerMontantTotal();

        if (empty($location->getStatut())) {
            $location->setStatut('réservée');
        }

        $em->persist($location);
        $em->flush();

        // ── Email de confirmation ──
$emailClient = trim((string) $request->request->get('email_client', ''));
        if (!empty($emailClient)) {
            try {
                $emailService->envoyerConfirmationLocation($location, $emailClient);
                $this->addFlash('info', 'Email de confirmation envoyé à ' . $emailClient);
            } catch (Exception $e) {
                $this->addFlash('warning', 'Email non envoyé : ' . $e->getMessage());
            }
        }

        // ── WhatsApp de confirmation ──
$whatsappTel = trim((string) $request->request->get('whatsapp_client', ''))
            ?: $location->getClientTelephone();

        if (!empty($whatsappTel)) {
            $r = $whatsAppService->envoyerConfirmationReservation($location, $whatsappTel);
            $this->addFlash(
                $r['succes'] ? 'info' : 'warning',
                '💬 WhatsApp : ' . $r['message']
            );
        }

        $this->addFlash('success', 'Location créée avec succès !');
        return $this->redirectToRoute('admin_location_index');
    }

    return $this->render('admin/location/new.html.twig', [
        'form'         => $form->createView(),
        'vehicules'    => $vehiculeRepository->findDisponibles(),
        'extras_dispo' => self::EXTRAS_DISPONIBLES,
    ]);
}
    // ══════════════════════════════════════════════════════════
    // 🔄 UPDATE STATUTS AUTOMATIQUE
    // ══════════════════════════════════════════════════════════
    #[Route('/update-statuts', name: 'admin_location_update_statuts', methods: ['POST'])]
    public function updateStatuts(LocationRepository $repository): Response
    {
        $count = $repository->mettreAJourStatutsAutomatique(new DateTime());
        $this->addFlash('success', $count . ' location(s) mise(s) à jour automatiquement.');
        return $this->redirectToRoute('admin_location_index');
    }
    #[Route('/test-whatsapp', name: 'admin_location_test_whatsapp', methods: ['GET'])]
public function testWhatsapp(WhatsAppService $whatsAppService): JsonResponse
{
    $resultat = $whatsAppService->envoyer(
        '+21694670088', // ← le numéro qui a rejoint le sandbox
        '🧪 Test WhatsApp depuis Horizia !'
    );
    return $this->json($resultat);
}
    // ══════════════════════════════════════════════════════════
    // 👁️ VOIR
    // ══════════════════════════════════════════════════════════
    #[Route('/{id}', name: 'admin_location_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location
    ): Response {
        return $this->render('admin/location/show.html.twig', [
            'location'     => $location,
            'extras_dispo' => self::EXTRAS_DISPONIBLES,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // ✏️ MODIFIER
    // ══════════════════════════════════════════════════════════
    #[Route('/{id}/edit', name: 'admin_location_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        EntityManagerInterface $em,
        VehiculeRepository $vehiculeRepository,
        WhatsAppService $whatsAppService
    ): Response {
        $ancienStatut = $location->getStatut();
        $form         = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Extras ──
            $extrasChoisis = $request->request->all('extras');
            $extrasData    = [];
            foreach ((array) $extrasChoisis as $cle) {
                if (isset(self::EXTRAS_DISPONIBLES[$cle])) {
                    $extrasData[$cle] = self::EXTRAS_DISPONIBLES[$cle]['prix'];
                }
            }
            $location->setExtras(empty($extrasData) ? null : $extrasData);
            $location->calculerMontantTotal();

            $vehicule = $location->getVehicule();
            if ($vehicule) {
                $nouvelEtat = in_array($location->getStatut(), ['terminée', 'annulée', 'no_show'])
                    ? 'disponible' : 'louee';
                $vehicule->setEtat($nouvelEtat);
                $em->persist($vehicule);
            }

            $em->flush();

            // ── WhatsApp automatique selon changement de statut ──
            $telephone     = $location->getClientTelephone();
            $nouveauStatut = $location->getStatut();

            if (!empty($telephone)) {
                if ($nouveauStatut === 'annulée' && $ancienStatut !== 'annulée') {
                    $r = $whatsAppService->envoyerAnnulation($location, $telephone);
                    $this->addFlash($r['succes'] ? 'info' : 'warning', '💬 WhatsApp annulation : ' . $r['message']);
                }
                if ($nouveauStatut === 'terminée' && $ancienStatut !== 'terminée') {
                    $r = $whatsAppService->envoyerNotificationRetour($location, $telephone);
                    $this->addFlash($r['succes'] ? 'info' : 'warning', '💬 WhatsApp retour : ' . $r['message']);
                }
            }

            $this->addFlash('success', 'Location modifiée avec succès !');
            return $this->redirectToRoute('admin_location_index');
        }

        return $this->render('admin/location/edit.html.twig', [
            'form'         => $form->createView(),
            'location'     => $location,
            'vehicules'    => $vehiculeRepository->findDisponibles(),
            'extras_dispo' => self::EXTRAS_DISPONIBLES,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 🗑️ SUPPRIMER
    // ══════════════════════════════════════════════════════════
    #[Route('/{id}/delete', name: 'admin_location_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        EntityManagerInterface $em
    ): Response {
if ($this->isCsrfTokenValid('delete' . $location->getIdLocation(), (string) $request->request->get('_token'))) {
                $vehicule = $location->getVehicule();
            if ($vehicule) {
                $vehicule->setEtat('disponible');
                $em->persist($vehicule);
            }
            $em->remove($location);
            $em->flush();
            $this->addFlash('success', 'Location supprimée avec succès !');
        }
        return $this->redirectToRoute('admin_location_index');
    }

    // ══════════════════════════════════════════════════════════
    // 💬 AJAX — ENVOYER WHATSAPP MANUELLEMENT depuis show.html
    // POST /admin/location/{id}/whatsapp
    // Params: type (confirmation|rappel|retour|annulation), telephone (optionnel)
    // ══════════════════════════════════════════════════════════
    #[Route('/{id}/whatsapp', name: 'admin_location_whatsapp', methods: ['POST'])]
    public function envoyerWhatsapp(
        #[MapEntity(mapping: ['id' => 'idLocation'])] Location $location,
        Request $request,
        WhatsAppService $whatsAppService
    ): JsonResponse {
        $type      = $request->request->get('type', 'confirmation');
$telephone = trim((string) $request->request->get('telephone', ''))
            ?: $location->getClientTelephone();

        if (empty($telephone)) {
            return $this->json(['success' => false, 'message' => 'Numéro de téléphone manquant.'], 400);
        }

        $resultat = match ($type) {
            'confirmation' => $whatsAppService->envoyerConfirmationReservation($location, $telephone),
            'rappel'       => $whatsAppService->envoyerRappel($location, $telephone),
            'retour'       => $whatsAppService->envoyerNotificationRetour($location, $telephone),
            'annulation'   => $whatsAppService->envoyerAnnulation($location, $telephone),
            default        => ['succes' => false, 'message' => 'Type inconnu : ' . $type],
        };

        return $this->json([
            'success' => $resultat['succes'],
            'message' => $resultat['message'],
        ], $resultat['succes'] ? 200 : 400);
    }

 // ══════════════════════════════════════════════════════════
// 📷 AJAX — SCANNER CIN (OCR)
// ══════════════════════════════════════════════════════════
#[Route('/scan-cin', name: 'admin_location_scan_cin', methods: ['POST'])]
public function scanCin(Request $request, OCRService $ocrService, LoggerInterface $logger): JsonResponse
{
    $logger->info('=== SCAN CIN START ===');
    
    $fichier = $request->files->get('image');

    if (!$fichier) {
        $logger->error('Aucune image reçue');
        return $this->json(['success' => false, 'message' => 'Aucune image reçue.'], 400);
    }
    
    $logger->info('Fichier reçu: ' . $fichier->getClientOriginalName() . ', type: ' . $fichier->getMimeType() . ', size: ' . $fichier->getSize());
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = strtolower($fichier->getClientOriginalExtension());
    if (!in_array($extension, $allowedExtensions)) {
        $logger->error('Format non supporté: ' . $extension);
        return $this->json(['success' => false, 'message' => 'Format non supporté. Utilisez JPG, PNG ou JPEG.'], 400);
    }
    
    if ($fichier->getSize() > 5 * 1024 * 1024) {
        $logger->error('Image trop lourde: ' . $fichier->getSize());
        return $this->json(['success' => false, 'message' => 'Image trop lourde (max 5 Mo).'], 400);
    }

    $tempDir = sys_get_temp_dir();
    $tempName = 'cin_' . uniqid() . '.' . $extension;
    $fichier->move($tempDir, $tempName);
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . $tempName;
    
    $logger->info('Fichier temporaire: ' . $tempPath);

    $resultat = [];
    try {
        $resultat = $ocrService->scannerCINRecto($tempPath);
        $logger->info('Résultat OCR: ' . json_encode($resultat));
    } catch (Exception $e) {
        $logger->error('Exception OCR: ' . $e->getMessage());
        $resultat = ['erreur' => $e->getMessage()];
    } finally {
        if (file_exists($tempPath)) {
            unlink($tempPath);
            $logger->info('Fichier temporaire supprimé');
        }
    }

    if (isset($resultat['erreur'])) {
        return $this->json(['success' => false, 'message' => 'Erreur OCR: ' . $resultat['erreur']], 500);
    }
    
    if (isset($resultat['cin'])) {
        return $this->json(['success' => true, 'cin' => $resultat['cin']]);
    }
    
    $message = $resultat['info'] ?? 'CIN non détectée. Assurez-vous que la photo est claire.';
    return $this->json(['success' => false, 'message' => $message], 404);
}
   // ══════════════════════════════════════════════════════════
// 🗺️ AJAX — GÉOLOCALISER UNE ADRESSE
// ══════════════════════════════════════════════════════════
#[Route('/geolocate', name: 'admin_location_geolocate', methods: ['POST'])]
public function geolocate(Request $request, GeolocationService $geolocationService): JsonResponse
{
    // Support des deux formats POST (form-data ou JSON)
    $adresse = $request->request->get('adresse');
    
    if (!$adresse && $request->getContent()) {
        $data = json_decode($request->getContent(), true);
        $adresse = $data['adresse'] ?? null;
    }
    
    $adresse = trim((string) $adresse);
    
    if (empty($adresse)) {
        return $this->json(['success' => false, 'message' => 'Adresse vide. Veuillez saisir une adresse complète.'], 400);
    }

    // Nettoyer l'adresse
    $adresse = (string) preg_replace('/\s+/', ' ', $adresse);
    
    try {
        $resultat = $geolocationService->geocoderAdresse($adresse);
    } catch (Exception $e) {
        return $this->json(['success' => false, 'message' => 'Erreur réseau : impossible de contacter le service de géolocalisation.'], 503);
    }

    if (isset($resultat['erreur'])) {
        return $this->json(['success' => false, 'message' => $resultat['erreur']], 404);
    }

    if (!isset($resultat['latitude']) || !isset($resultat['longitude'])) {
        return $this->json(['success' => false, 'message' => 'Adresse non trouvée. Vérifiez l\'adresse saisie.'], 404);
    }

    try {
        $distanceKm = $geolocationService->calculerDistanceDepuisAgence((float) $resultat['latitude'], (float) $resultat['longitude']);
        $distanceFormatee = $geolocationService->formaterDistance($distanceKm);
    } catch (Exception $e) {
        $distanceFormatee = null;
    }

    return $this->json([
        'success'          => true,
        'latitude'         => (float) $resultat['latitude'],
        'longitude'        => (float) $resultat['longitude'],
        'adresse_formatee' => $resultat['adresse_formatee'] ?? $adresse,
        'ville'            => $resultat['ville'] ?? '',
        'code_postal'      => $resultat['code_postal'] ?? '',
        'distance_agence'  => $distanceFormatee,
    ]);
}
#[Route('/test-ocr-key', name: 'test_ocr_key')]
public function testOCRKey(OCRService $ocrService): JsonResponse
{
    $testImagePath = __DIR__ . '/../../public/test-cin.jpg';
    
    if (!file_exists($testImagePath)) {
        return $this->json(['error' => 'Veuillez placer une image test-cin.jpg dans le dossier public'], 400);
    }
    
    $resultat = $ocrService->scannerCINRecto($testImagePath);
    
    return $this->json($resultat);
}
#[Route('/test-whatsapp-config', name: 'admin_location_test_whatsapp_config', methods: ['GET'])]
public function testWhatsappConfig(WhatsAppService $whatsAppService): JsonResponse
{
    $resultat = $whatsAppService->testerConnexion();
    return $this->json($resultat);
}

#[Route('/test-ocr', name: 'admin_location_test_ocr', methods: ['GET'])]
public function testOCR(OCRService $ocrService): JsonResponse
{
    $resultat = $ocrService->testerCleAPI();
    return $this->json($resultat);
}
#[Route('/diagnostic-whatsapp', name: 'admin_location_diagnostic_whatsapp', methods: ['GET'])]
public function diagnosticWhatsapp(WhatsAppService $whatsAppService, LoggerInterface $logger): JsonResponse
{
    $resultats = [
        'twilio_config' => [
            'account_sid_exists' => !empty($_ENV['TWILIO_ACCOUNT_SID']),
            'auth_token_exists' => !empty($_ENV['TWILIO_AUTH_TOKEN']),
            'whatsapp_from_exists' => !empty($_ENV['TWILIO_WHATSAPP_FROM']),
            'account_sid_prefix' => substr($_ENV['TWILIO_ACCOUNT_SID'] ?? '', 0, 5) . '...',
            'whatsapp_from' => $_ENV['TWILIO_WHATSAPP_FROM'] ?? 'non défini',
        ],
        'test_connexion' => $whatsAppService->testerConnexion(),
        'php_extensions' => [
            'curl' => extension_loaded('curl'),
            'openssl' => extension_loaded('openssl'),
            'json' => extension_loaded('json'),
        ],
        'twilio_sandbox_instructions' => [
            '1. Activez le sandbox WhatsApp sur votre compte Twilio',
            '2. Envoyez "join <votre-mot>" au +14155238886',
            '3. Le numéro doit être au format +216XXXXXXXX',
        ]
    ];
    
    return $this->json($resultats);
}
}