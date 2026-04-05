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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;

#[Route('/admin/location')]
class LocationController extends AbstractController
{
    // ─────────────────────────────────────────────────────────
    // Liste des extras disponibles (libellé + prix fixe en TND)
    // ─────────────────────────────────────────────────────────
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

        $locations = !empty($recherche)
            ? $repository->rechercherParClient($recherche)
            : $repository->findBy([], ['dateDebut' => 'DESC']);

        return $this->render('admin/location/index.html.twig', [
            'locations' => $locations,
            'recherche' => $recherche,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // ➕ NOUVEAU
    // ══════════════════════════════════════════════════════════
    #[Route('/new', name: 'admin_location_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        VehiculeRepository $vehiculeRepository,
        ContratService $contratService,
        EmailService $emailService
    ): Response {
        $location = new Location();
        $form     = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ Gérer les extras cochés
            $extrasChoisis = $request->request->all('extras');
            $extrasData    = [];
            foreach ((array) $extrasChoisis as $cle) {
                if (isset(self::EXTRAS_DISPONIBLES[$cle])) {
                    $extrasData[$cle] = self::EXTRAS_DISPONIBLES[$cle]['prix'];
                }
            }
            $location->setExtras(empty($extrasData) ? null : $extrasData);

            // ✅ Calcul automatique du montant total
            $location->calculerMontantTotal();

            // Statut par défaut
            if (empty($location->getStatut())) {
                $location->setStatut('réservée');
            }

            // Mettre le véhicule en "louee"
            $vehicule = $location->getVehicule();
            if ($vehicule) {
                $vehicule->setEtat('louee');
                $em->persist($vehicule);
            }

            $em->persist($location);
            $em->flush();

            // Email de confirmation (optionnel)
            $emailClient = trim($request->request->get('email_client', ''));
            if (!empty($emailClient)) {
                try {
                    $emailService->envoyerConfirmationLocation($location, $emailClient);
                    $this->addFlash('info', 'Email de confirmation envoyé à ' . $emailClient);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Email non envoyé : ' . $e->getMessage());
                }
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
        VehiculeRepository $vehiculeRepository
    ): Response {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ Gérer les extras cochés
            $extrasChoisis = $request->request->all('extras');
            $extrasData    = [];
            foreach ((array) $extrasChoisis as $cle) {
                if (isset(self::EXTRAS_DISPONIBLES[$cle])) {
                    $extrasData[$cle] = self::EXTRAS_DISPONIBLES[$cle]['prix'];
                }
            }
            $location->setExtras(empty($extrasData) ? null : $extrasData);

            // ✅ Recalcul automatique du montant total
            $location->calculerMontantTotal();

            // Mettre à jour l'état du véhicule
            $vehicule = $location->getVehicule();
            if ($vehicule) {
                $nouvelEtat = in_array($location->getStatut(), ['terminée', 'annulée', 'no_show'])
                    ? 'disponible'
                    : 'louee';
                $vehicule->setEtat($nouvelEtat);
                $em->persist($vehicule);
            }

            $em->flush();
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
        if ($this->isCsrfTokenValid('delete' . $location->getIdLocation(), $request->request->get('_token'))) {
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
    // 📷 AJAX — SCANNER CIN (OCR)
    // POST /admin/location/scan-cin
    // Body (multipart): image = fichier image de la CIN
    // Retourne : { success, cin } ou { success: false, message }
    // ══════════════════════════════════════════════════════════
    #[Route('/scan-cin', name: 'admin_location_scan_cin', methods: ['POST'])]
    public function scanCin(Request $request, OCRService $ocrService): JsonResponse
    {
        $fichier = $request->files->get('image');

        if (!$fichier) {
            return $this->json(['success' => false, 'message' => 'Aucune image reçue.'], 400);
        }

        // ✅ Vérifier la taille AVANT de déplacer (getSize() fonctionne sur l'objet UploadedFile)
        if ($fichier->getSize() > 5 * 1024 * 1024) {
            return $this->json(['success' => false, 'message' => 'Image trop lourde (max 5 Mo).'], 400);
        }

        // ✅ Déplacer d'abord dans un répertoire temporaire
        $tempDir  = sys_get_temp_dir();
        $ext      = $fichier->getClientOriginalExtension() ?: 'jpg';
        $tempName = 'cin_' . uniqid() . '.' . $ext;
        $fichier->move($tempDir, $tempName);
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $tempName;

        // ✅ Vérifier le type MIME sur le fichier réel (pas sur l'objet UploadedFile)
        $typesAutorises = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mimeDetecte    = mime_content_type($tempPath) ?: 'unknown';
        if (!in_array($mimeDetecte, $typesAutorises)) {
            unlink($tempPath);
            return $this->json([
                'success' => false,
                'message' => 'Format non supporté (' . $mimeDetecte . '). Utilisez JPG ou PNG.',
            ], 400);
        }

        $resultat = [];
        try {
            $resultat = $ocrService->scannerCINRecto($tempPath);
        } catch (\Exception $e) {
            $resultat = ['erreur' => $e->getMessage()];
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        if (isset($resultat['erreur'])) {
            return $this->json(['success' => false, 'message' => $resultat['erreur']], 500);
        }

        if (isset($resultat['cin'])) {
            return $this->json(['success' => true, 'cin' => $resultat['cin']]);
        }

        return $this->json([
            'success' => false,
            'message' => $resultat['info'] ?? 'CIN non détectée. Vérifiez la qualité de l\'image.',
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // 🗺️ AJAX — GÉOLOCALISER UNE ADRESSE
    // POST /admin/location/geolocate
    // Body (JSON ou form): adresse = string
    // Retourne : { success, latitude, longitude, adresse_formatee, ville, code_postal }
    // ══════════════════════════════════════════════════════════
    #[Route('/geolocate', name: 'admin_location_geolocate', methods: ['POST'])]
    public function geolocate(Request $request, GeolocationService $geolocationService): JsonResponse
    {
        $adresse = $request->request->get('adresse')
            ?? (json_decode($request->getContent(), true)['adresse'] ?? null);

        if (empty(trim((string) $adresse))) {
            return $this->json(['success' => false, 'message' => 'Adresse vide.'], 400);
        }

        try {
            $resultat = $geolocationService->geocoderAdresse(trim($adresse));
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur réseau : impossible de contacter le service de géolocalisation.',
            ], 503);
        }

        if (isset($resultat['erreur'])) {
            return $this->json(['success' => false, 'message' => $resultat['erreur']], 404);
        }

        try {
            $distanceKm = $geolocationService->calculerDistanceDepuisAgence(
                (float) $resultat['latitude'],
                (float) $resultat['longitude']
            );
            $distanceFormatee = $geolocationService->formaterDistance($distanceKm);
        } catch (\Exception $e) {
            $distanceFormatee = null;
        }

        return $this->json([
            'success'          => true,
            'latitude'         => $resultat['latitude'],
            'longitude'        => $resultat['longitude'],
            'adresse_formatee' => $resultat['adresse_formatee'] ?? '',
            'ville'            => $resultat['ville']            ?? '',
            'code_postal'      => $resultat['code_postal']      ?? '',
            'distance_agence'  => $distanceFormatee,
        ]);
    }
}