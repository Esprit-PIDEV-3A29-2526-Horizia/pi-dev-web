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

        $html = '';
        foreach ($result['reservations'] as $reservation) {
            $chambreTypeDesc = $chambreTypeService->getChambreType(
                $reservation->getAdultes(),
                $reservation->getEnfants(),
                $reservation->getNombreChambres()
            );
            $pension = $reservation->getModeReservation();
            $pensionLabel = $pension ? str_replace('_', ' ', $pension) : '-';

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
                            <div class="detail-item"><small><i class="fa fa-bed"></i> Chambres</small><p>' . htmlspecialchars($chambreTypeDesc) . '</p></div>
                            <div class="detail-item"><small><i class="fa fa-cutlery"></i> Pension</small><p>' . htmlspecialchars($pensionLabel) . '</p></div>
                        </div>
                        <div class="card-actions">
                            <a href="#" class="btn-action btn-edit" data-id="' . $reservation->getIdreslog() . '"><i class="fa fa-pencil"></i> Modifier</a>
                            <a href="#" class="btn-action btn-delete" data-id="' . $reservation->getIdreslog() . '"><i class="fa fa-trash"></i> Supprimer</a>';
            if ($reservation->getStatus() == 'confirmée') {
                $html .= '<a href="#" class="btn-action btn-qr" data-id="' . $reservation->getIdreslog() . '"><i class="fa fa-qrcode"></i> QR Code</a>';
            }
            $html .= '</div></div></div></div>';
        }

        if (empty($result['reservations'])) {
            $html = '<div class="col-12"><div class="text-center py-5 bg-light rounded-4"><i class="bi bi-calendar-x display-1 text-muted mb-4 d-block"></i><h3 class="text-muted mb-3">Aucune réservation</h3><p class="text-muted mb-4">Commencez par réserver un logement.</p><a href="' . $this->generateUrl('app_front_logement_index') . '" class="btn btn-horozia-primary btn-lg px-5 rounded-pill"><i class="bi bi-house-door me-2"></i> Découvrir les logements</a></div></div>';
        }

        return $this->json(['html' => $html]);
    }

    #[Route('/reservation/delete/{id}', name: 'app_front_reservation_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em,EmailService $emailService): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }
        
        if ($reservation->getUser()->getId() !== 14) {
            return $this->json(['success' => false, 'error' => 'Vous n\'êtes pas autorisé à supprimer cette réservation'], 403);
        }
        
        $em->remove($reservation);
        $em->flush();
        $emailService->sendCancellationEmail($reservation->getUser()->getEmail(), $reservation, 'Suppression par l\'utilisateur');
        return $this->json(['success' => true]);
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

    $qrCodeDataUri = $qrCodeService->generateQrCodeDataUri($content);

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
    public function sendEmail(int $id, EntityManagerInterface $em, PdfService $pdfService, EmailService $emailService): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getUser()->getId() !== 14) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }
        
        try {
            $pdfContent = $pdfService->generateReservationPdf($reservation);
            $success = $emailService->sendReservationEmail($reservation->getUser()->getEmail(), $reservation, $pdfContent);
            
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

        $html = $this->renderView('front/reservationlog/edit.html.twig', [
            'reservation' => $reservation,
            'logement' => $logement,
        ]);
        return new Response($html);
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

        $errors = [];
        if (!$dateArrivee || !$dateDepart) {
            $errors[] = 'Dates invalides.';
        } elseif ($dateArrivee < new \DateTime() || $dateDepart <= $dateArrivee) {
            $errors[] = 'Les dates doivent être valides (départ après arrivée, et non passées).';
        }
        if ($adultes < 1) $errors[] = 'Au moins 1 adulte.';
        if ($enfants < 0) $errors[] = 'Nombre d\'enfants invalide.';
        if ($nombreChambres < 1) $errors[] = 'Au moins 1 chambre.';
        if ($modeReservation && !in_array($modeReservation, ['all_inclusive', 'demi_pension', 'petit_dejeuner', 'soft'])) {
            $errors[] = 'Formule de pension invalide.';
        }

        if (!empty($errors)) {
            return $this->json(['success' => false, 'error' => implode(' ', $errors)]);
        }

        $nuits = $dateArrivee->diff($dateDepart)->days;
        $montant = $nuits * $logement->getTarifNuit();

        $reservation->setDateDebut($dateArrivee);
        $reservation->setDateFin($dateDepart);
        $reservation->setMontant($montant);
        $reservation->setModalites($modalite);
        $reservation->setAdultes($adultes);
        $reservation->setEnfants($enfants);
        $reservation->setNombreChambres($nombreChambres);
        $reservation->setModeReservation($modeReservation);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Réservation modifiée avec succès']);
    }
}