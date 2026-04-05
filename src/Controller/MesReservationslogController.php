<?php

namespace App\Controller;

use App\Entity\Reservationlog;
use App\Entity\User;
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

class MesReservationslogController extends AbstractController
{
    #[Route('/mes-reservations', name: 'app_front_reservation_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find(4);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé (ID 4)');
        }
        return $this->render('front/reservationlog/index.html.twig');
    }

    #[Route('/mes-reservations/ajax', name: 'app_front_reservation_ajax', methods: ['GET'])]
    public function ajax(Request $request, ReservationlogSearchService $searchService, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find(4);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'date_desc');

        $result = $searchService->getFilteredReservationsByUser($user, $search, $status, $sort, 1, 50);
        
        $html = '';
        foreach ($result['reservations'] as $reservation) {
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
                            <div class="detail-item">
                                <small><i class="fa fa-calendar"></i> Arrivée</small>
                                <p>' . $reservation->getDateDebut()->format('d/m/Y') . '</p>
                            </div>
                            <div class="detail-item">
                                <small><i class="fa fa-calendar"></i> Départ</small>
                                <p>' . $reservation->getDateFin()->format('d/m/Y') . '</p>
                            </div>
                            <div class="detail-item">
                                <small><i class="fa fa-money"></i> Montant</small>
                                <p>' . number_format($reservation->getMontant(), 2, ',', ' ') . ' DT</p>
                            </div>
                            <div class="detail-item">
                                <small><i class="fa fa-credit-card"></i> Modalité</small>
                                <p>' . htmlspecialchars($reservation->getModalites()) . '</p>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="#" class="btn-action" title="Modifier"><i class="fa fa-pencil"></i> Modifier</a>
                            <a href="#" class="btn-action btn-delete" data-id="' . $reservation->getIdreslog() . '" title="Supprimer"><i class="fa fa-trash"></i> Supprimer</a>';
            if ($reservation->getStatus() == 'confirmée') {
                $html .= '<a href="#" class="btn-action btn-qr" data-id="' . $reservation->getIdreslog() . '" title="QR Code"><i class="fa fa-qrcode"></i> QR Code</a>';
            }
            $html .= '</div>
                    </div>
                </div>
            </div>';
        }
        if (empty($result['reservations'])) {
            $html = '<div class="col-12">
                <div class="text-center py-5 bg-light rounded-4">
                    <i class="bi bi-calendar-x display-1 text-muted mb-4 d-block"></i>
                    <h3 class="text-muted mb-3">Aucune réservation</h3>
                    <p class="text-muted mb-4">Commencez par réserver un logement.</p>
                    <a href="' . $this->generateUrl('app_front_logement_index') . '" class="btn btn-horozia-primary btn-lg px-5 rounded-pill">
                        <i class="bi bi-house-door me-2"></i> Découvrir les logements
                    </a>
                </div>
            </div>';
        }
        
        return $this->json(['html' => $html]);
    }

    #[Route('/reservation/delete/{id}', name: 'app_front_reservation_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }
        
        if ($reservation->getUser()->getId() !== 4) {
            return $this->json(['success' => false, 'error' => 'Vous n\'êtes pas autorisé à supprimer cette réservation'], 403);
        }
        
        $em->remove($reservation);
        $em->flush();
        
        return $this->json(['success' => true]);
    }

    #[Route('/reservation/qrcode/{id}', name: 'app_front_reservation_qrcode', methods: ['GET'])]
public function qrcode(int $id, EntityManagerInterface $em, QrCodeService $qrCodeService): Response
{
    $reservation = $em->getRepository(Reservationlog::class)->find($id);
    if (!$reservation || $reservation->getUser()->getId() !== 4) {
        throw $this->createNotFoundException();
    }
    
    $content = "Réservation #" . $reservation->getIdreslog() . "\n";
    $content .= "Logement: " . $reservation->getLogement()->getNom() . "\n";
    $content .= "Arrivée: " . $reservation->getDateDebut()->format('d/m/Y') . "\n";
    $content .= "Départ: " . $reservation->getDateFin()->format('d/m/Y') . "\n";
    $content .= "Montant: " . number_format($reservation->getMontant(), 2, ',', ' ') . " DT\n";
    $content .= "Modalité: " . $reservation->getModalites();
    
    $qrCodeDataUri = $qrCodeService->generateQrCodeDataUri($content);
    
    $html = '
        <div class="text-center">
            <img src="' . $qrCodeDataUri . '" class="img-fluid mb-3" style="max-width: 250px;">
            <h5>Réservation #' . $reservation->getIdreslog() . '</h5>
            <p><strong>' . htmlspecialchars($reservation->getLogement()->getNom()) . '</strong></p>
            <p>' . $reservation->getDateDebut()->format('d/m/Y') . ' → ' . $reservation->getDateFin()->format('d/m/Y') . '</p>
            <p>' . number_format($reservation->getMontant(), 2, ',', ' ') . ' DT</p>
            <button id="sendEmailBtn" class="btn btn-primary mt-2">📧 Envoyer par email (PDF)</button>
            <div id="emailMessage" class="mt-3"></div>
        </div>
        <script>
            document.getElementById("sendEmailBtn").addEventListener("click", function() {
                const btn = this;
                const msgDiv = document.getElementById("emailMessage");
                btn.disabled = true;
                btn.innerHTML = "⏳ Envoi...";
                fetch("' . $this->generateUrl('app_front_reservation_send_email', ['id' => $reservation->getIdreslog()]) . '", {
                    method: "POST",
                    headers: { "X-Requested-With": "XMLHttpRequest" }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        msgDiv.innerHTML = "<div class=\"alert alert-success\">Email envoyé !</div>";
                    } else {
                        msgDiv.innerHTML = "<div class=\"alert alert-danger\">Erreur : " + (data.error || "Envoi échoué") + "</div>";
                    }
                })
                .catch(() => {
                    msgDiv.innerHTML = "<div class=\"alert alert-danger\">Erreur réseau.</div>";
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = "📧 Envoyer par email (PDF)";
                });
            });
        </script>';
    
    return new Response($html);
}
    #[Route('/reservation/send-email/{id}', name: 'app_front_reservation_send_email', methods: ['POST'])]
    public function sendEmail(int $id, EntityManagerInterface $em, PdfService $pdfService, EmailService $emailService): JsonResponse
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getUser()->getId() !== 4) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }
        
        $user = $reservation->getUser();
        $pdfContent = $pdfService->generateReservationPdf($reservation);
        $emailSent = $emailService->sendReservationEmail($user->getEmail(), $reservation, $pdfContent);
        
        if ($emailSent) {
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email']);
    }
}