<?php
// src/Controller/MesReservationslogController.php

namespace App\Controller;

use App\Entity\Reservationlog;
use App\Entity\User;
use App\Service\ChambreTypeService;
use App\Service\EmailService;
use App\Service\PdfService;
use App\Service\QrCodeService;
use App\Service\ReservationlogSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function Symfony\Component\Clock\now;

class MesReservationslogController extends AbstractController
{
    // Page principale (affiche le template, les données sont chargées en AJAX)
    #[Route('/mes-reservations', name: 'app_front_reservation_index')]
    public function index(): Response
    {
        return $this->render('front/reservationlog/index.html.twig');
    }

    // Route AJAX pour récupérer les cartes filtrées
   #[Route('/mes-reservations/ajax', name: 'app_front_reservation_ajax', methods: ['GET'])]
    public function ajax(Request $request, ReservationlogSearchService $searchService, EntityManagerInterface $em, ChambreTypeService $chambreTypeService): JsonResponse
    {
        $user = $em->getRepository(User::class)->find(14);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'date_desc');

        $result = $searchService->getFilteredReservationsByUser($user, $search, $status, $sort, 1, 50);
 $filteredReservations = array_filter($result['reservations'], function($reservation) {
        return $reservation->getStatus() !== 'annulée';
    });
        $html = '';
        foreach ($filteredReservations as $reservation) {
    $chambreTypeDesc = $chambreTypeService->getChambreType(
        $reservation->getAdultes(),
        $reservation->getEnfants(),
        $reservation->getNombreChambres()
    );
    $pension = $reservation->getModeReservation();
    $pensionLabel = $pension ? str_replace('_', ' ', $pension) : '-';
    
    // Affichage des chambres
    $chambresDisplay = $chambreTypeDesc;
    if ($reservation->getRepartitionChambres()) {
        $formatted = $chambreTypeService->formatRepartition($reservation->getRepartitionChambres());
        if ($formatted) $chambresDisplay = $formatted;
    }

    $html .= '<div class="col-md-6 col-lg-4" data-id="' . $reservation->getIdreslog() . '">
        <div class="reservation-card">
            <div class="reservation-card-content">
                <div class="card-header">
                    <h5>' . htmlspecialchars($reservation->getLogement()->getNom()) . '</h5>
                    <span class="badge-status ' . ($reservation->getStatus() == 'confirmée' ? 'confirmed' : ($reservation->getStatus() == 'en_attente' ? 'pending' : 'cancelled')) . '">
                        ' . htmlspecialchars($reservation->getStatus()) . '
                    </span>
                </div>
                <div class="card-details">
                    <div class="detail-item"><small><i class="fa fa-calendar"></i> Arrivée</small><p>' . $reservation->getDateDebut()->format('d/m/Y') . '</p></div>
                    <div class="detail-item"><small><i class="fa fa-calendar"></i> Départ</small><p>' . $reservation->getDateFin()->format('d/m/Y') . '</p></div>
                    <div class="detail-item"><small><i class="fa fa-money"></i> Montant</small><p>' . number_format($reservation->getMontant(), 2, ',', ' ') . ' DT</p></div>
                    <div class="detail-item"><small><i class="fa fa-credit-card"></i> Modalité</small><p>' . htmlspecialchars($reservation->getModalites()) . '</p></div>
                    <div class="detail-item"><small><i class="fa fa-users"></i> Occupants</small><p>' . $reservation->getAdultes() . ' adulte(s) + ' . $reservation->getEnfants() . ' enfant(s)</p></div>
                    <div class="detail-item"><small><i class="fa fa-bed"></i> Chambres</small><p>' . htmlspecialchars($chambresDisplay) . '</p></div>
                    <div class="detail-item"><small><i class="fa fa-cutlery"></i> Pension</small><p>' . htmlspecialchars($pensionLabel) . '</p></div>
                </div>
                <div class="card-actions">';
    
    // Boutons (Modifier, Supprimer, QR, Finaliser)
    if ($reservation->getStatus() !== 'terminée') {
        $html .= '<a href="#" class="btn-action btn-edit" data-id="' . $reservation->getIdreslog() . '"><i class="fa fa-pencil"></i> Modifier</a>';
  $html .= '<a href="#" class="btn-action btn-delete" data-id="' . $reservation->getIdreslog() . '" data-created-at="' . $reservation->getCreatedAt()->getTimestamp() . '"><i class="fa fa-trash"></i> Supprimer</a>';    }
    if ($reservation->getStatus() == 'confirmée') {
        $html .= '<a href="#" class="btn-action btn-qr" data-id="' . $reservation->getIdreslog() . '"><i class="fa fa-qrcode"></i> QR Code</a>';
    }
    if ($reservation->getStatus() == 'en_attente' && $reservation->getModalites() == 'En ligne') {
        $html .= '<a href="' . $this->generateUrl('app_front_reservation_pay', ['id' => $reservation->getIdreslog()]) . '" class="btn-action btn-pay" style="background: #23779C; color:white;"><i class="fa fa-credit-card"></i> Finaliser paiement</a>';
    }
    
    $html .= '</div></div></div></div>';
}

        if (empty($result['reservations'])) {
            $html = '<div class="col-12"><div class="text-center py-5 bg-light rounded-4"><i class="bi bi-calendar-x display-1 text-muted mb-4 d-block"></i><h3 class="text-muted mb-3">Aucune réservation</h3><p class="text-muted mb-4">Commencez par réserver un logement.</p><a href="' . $this->generateUrl('app_front_logement_index') . '" class="btn btn-horozia-primary btn-lg px-5 rounded-pill"><i class="bi bi-house-door me-2"></i> Découvrir les logements</a></div></div>';
        }

        return $this->json(['html' => $html]);
    }

    
  #[Route('/reservation/delete/{id}', name: 'app_front_reservation_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }

        if ($reservation->getUser()->getId() !== 14) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $now = new \DateTime();
        $createdAt = $reservation->getCreatedAt();
        $ageEnHeures = ($now->getTimestamp() - $createdAt->getTimestamp()) / 3600;

        if ($ageEnHeures >= 1) {
            return $this->json(['success' => false, 'error' => 'Cette réservation a plus d\'1 heure, utilisez la demande d\'annulation.'], 400);
        }

        $em->remove($reservation);
        $em->flush();
        $emailService->sendCancellationEmail($reservation->getUser()->getEmail(), $reservation, 'Suppression par l\'utilisateur (moins d\'1h)');
        return $this->json(['success' => true, 'message' => 'Réservation supprimée.']);
    }

    // Demande d'annulation (plus d'1h)
    // Demande d'annulation (plus d'1h)
#[Route('/reservation/request-cancel/{id}', name: 'app_front_reservation_request_cancel', methods: ['POST'])]
public function requestCancel(int $id, EntityManagerInterface $em): JsonResponse
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    
    // L'utilisateur "normal" est l'ID 14 (celui qui fait les réservations)
    $userId = 14;
    
    // Vérifier que la réservation existe et appartient à l'utilisateur ID 14
    if (!$reservation || $reservation->getUser()->getId() !== $userId) {
        return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
    }
    
    // Seules les réservations confirmées peuvent être annulées
    if (!in_array($reservation->getStatus(), ['confirmée', 'en_attente'])) {
        return $this->json(['success' => false, 'error' => 'Seules les réservations confirmées peuvent être annulées.'], 400);
    }
    
    // Passer en demande d'annulation
    $reservation->setStatus('demande_annulation');
    $em->flush();
    
    return $this->json(['success' => true, 'message' => 'Demande d\'annulation envoyée à l\'administrateur.']);
}

#[Route('/reservation/qrcode/{id}', name: 'app_front_reservation_qrcode', methods: ['GET'])]
public function qrcode(int $id, EntityManagerInterface $em, QrCodeService $qrCodeService): Response
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    if (!$reservation || $reservation->getUser()->getId() !== 14) {
        throw $this->createNotFoundException();
    }

    // Contenu du QR code (scannable) – l'ID n'est pas affiché dans le HTML
    $content = "Logement: " . $reservation->getLogement()->getNom() . "\n";
    $content .= "Arrivée: " . $reservation->getDateDebut()->format('d/m/Y') . "\n";
    $content .= "Départ: " . $reservation->getDateFin()->format('d/m/Y') . "\n";
    $content .= "Adultes: " . $reservation->getAdultes() . "\n";
    $content .= "Enfants: " . $reservation->getEnfants() . "\n";
    $content .= "Chambres: " . $reservation->getNombreChambres() . "\n";
    $content .= "Pension: " . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . "\n";
    $content .= "Montant: " . number_format($reservation->getMontant(), 2, ',', ' ') . " DT\n";
    $content .= "Modalité: " . $reservation->getModalites() . "\n";
    $content .= "Statut: " . $reservation->getStatus();

    $qrCodeDataUri = $qrCodeService->generateQrCodeBase64($content);

    $html = '
    <div class="text-center">
        <img src="' . $qrCodeDataUri . '" class="img-fluid mb-3" style="max-width: 250px;">
        <h5>' . htmlspecialchars($reservation->getLogement()->getNom()) . '</h5>
        <p>' . $reservation->getDateDebut()->format('d/m/Y') . ' → ' . $reservation->getDateFin()->format('d/m/Y') . '</p>
        <p>👥 ' . $reservation->getAdultes() . ' adulte(s) + ' . $reservation->getEnfants() . ' enfant(s)</p>
        <p>🛏️ ' . $reservation->getNombreChambres() . ' chambre(s)</p>
        <p>🍽️ ' . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . '</p>
        <p>💰 ' . number_format($reservation->getMontant(), 2, ',', ' ') . ' DT</p>
        <p>💳 ' . htmlspecialchars($reservation->getModalites()) . '</p>
        <p>📌 ' . htmlspecialchars($reservation->getStatus()) . '</p>
        <a href="' . $this->generateUrl('app_front_reservation_download_pdf', ['id' => $reservation->getIdreslog()]) . '" class="btn btn-primary mt-2" target="_blank">
            <i class="fa fa-file-pdf"></i> Télécharger la réservation (PDF)
        </a>
    </div>';

    return new Response($html);
}
#[Route('/reservation/download-pdf/{id}', name: 'app_front_reservation_download_pdf', methods: ['GET'])]
public function downloadPdf(int $id, EntityManagerInterface $em, PdfService $pdfService): Response
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    if (!$reservation || $reservation->getUser()->getId() !== 14) {
        throw $this->createNotFoundException();
    }
    
    $pdfContent = $pdfService->generateReservationPdf($reservation);
    
    return new Response($pdfContent, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="reservation.pdf"',
    ]);
}
    #[Route('/reservation/send-email/{id}', name: 'app_front_reservation_send_email', methods: ['POST'])]
    public function sendEmail(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getUser()->getId() !== 14) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }
        
        try {
            // Message personnalisé (peut être dynamique)
            $customMessage = "✅ Votre réservation a bien été enregistrée.";
            $success = $emailService->sendReservationEmail($reservation->getUser()->getEmail(), $reservation, $customMessage);
            
            if ($success) {
                return $this->json(['success' => true, 'message' => '✅ Email envoyé avec succès !']);
            } else {
                return $this->json(['success' => false, 'error' => '❌ Échec de l\'envoi. Vérifiez les logs.']);
            }
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
#[Route('/reservation/edit-modal/{id}', name: 'app_front_reservation_edit_modal', methods: ['GET'])]
public function editModal(int $id, EntityManagerInterface $em): Response
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    if (!$reservation || $reservation->getUser()->getId() !== 14) {
        throw $this->createNotFoundException();
    }
    $logement = $reservation->getLogement();

    return $this->render('front/reservationlog/edit.html.twig', [
        'reservation' => $reservation,
        'logement' => $logement,
    ]);
}
    #[Route('/reservation/edit/{id}', name: 'app_front_reservation_edit', methods: ['POST'])]
public function edit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    if (!$reservation || $reservation->getUser()->getId() !== 14) {
        return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
    }
    $logement = $reservation->getLogement();

    $dateArrivee = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_arrivee'));
    $dateDepart  = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_depart'));
    $modalite    = $request->request->get('modalite');
    $adultes     = (int)$request->request->get('adultes', 1);
    $enfants     = (int)$request->request->get('enfants', 0);
    $nombreChambres = (int)$request->request->get('nombre_chambres', 1);
    $modeReservation = $request->request->get('mode_reservation');
    $repartitionChambres = $request->request->get('repartition_chambres');

    // Forcer 1 chambre pour les non-hôtels
    $typeLogement = strtolower($logement->getType());
    $isHotel = ($typeLogement === 'hôtel' || $typeLogement === 'hotel');
    if (!$isHotel) {
        $nombreChambres = 1;
    }

    // ... reste de la validation et calcul ...

    $errors = [];
    if (!$dateArrivee || !$dateDepart) {
        $errors[] = 'Dates invalides.';
    } elseif ($dateArrivee < new \DateTime() || $dateDepart <= $dateArrivee) {
        $errors[] = 'Les dates doivent être valides (départ après arrivée, et non passées).';
    }
    if ($adultes < 1) $errors[] = 'Au moins 1 adulte.';
    if ($enfants < 0) $errors[] = 'Nombre d\'enfants invalide.';
    if ($nombreChambres < 1) $errors[] = 'Au moins 1 chambre.';
    if (empty($modeReservation) || !in_array($modeReservation, ['all_inclusive', 'all_inclusive_soft(sans_alcool)', 'demi_pension', 'logement_petit_dejeuner'])) {
    $errors[] = 'Formule de pension invalide.';
}

    if (!empty($errors)) {
        return $this->json(['success' => false, 'error' => implode(' ', $errors)]);
    }

    // Calcul du montant avec pension et nombre de personnes
    $nuits = $dateArrivee->diff($dateDepart)->days;
    $nombrePersonnes = $adultes + $enfants;
    $prixBaseParNuitParPersonne = $logement->getTarifNuit();
    $coefficient = $this->getPensionCoefficient($modeReservation);
    $montant = $nuits * $nombrePersonnes * $prixBaseParNuitParPersonne * $coefficient;

    $reservation->setDateDebut($dateArrivee);
    $reservation->setDateFin($dateDepart);
    $reservation->setMontant($montant);
    $reservation->setModalites($modalite);
    $reservation->setAdultes($adultes);
    $reservation->setEnfants($enfants);
    $reservation->setNombreChambres($nombreChambres);
    $reservation->setModeReservation($modeReservation);
    if ($repartitionChambres !== null) {
        $reservation->setRepartitionChambres($repartitionChambres);
    }
    $em->flush();

    return $this->json(['success' => true, 'message' => 'Réservation modifiée avec succès']);
}

// Ajoutez cette méthode privée si elle n’existe pas
private function getPensionCoefficient(?string $modeReservation): float
{
    return match ($modeReservation) {
        'demi_pension' => 1.20,
        'all_inclusive' => 1.45,
        'all_inclusive_soft(sans_alcool)' => 1.37,
        'logement_petit_dejeuner' => 1.00,
        default => 1.00,
    };
}
public function formatRepartition(?string $repartition): string
{
    if (!$repartition) return '';
    $parts = explode(',', $repartition);
    $types = [];
    foreach ($parts as $p) {
        $p = (int)trim($p);
        if ($p == 1) $types[] = 'simple';
        elseif ($p == 2) $types[] = 'double';
        elseif ($p == 3) $types[] = 'triple';
        else $types[] = 'quadruple';
    }
    $compteur = array_count_values($types);
    $description = [];
    foreach ($compteur as $type => $count) {
        $description[] = $count . ' chambre' . ($count > 1 ? 's' : '') . ' ' . $type;
    }
    return implode(' + ', $description);
}
}