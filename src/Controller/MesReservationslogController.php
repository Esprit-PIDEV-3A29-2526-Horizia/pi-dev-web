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

class MesReservationslogController extends AbstractController
{

    #[Route('/mes-reservations', name: 'app_front_reservation_index')]
public function index(Request $request, ReservationlogSearchService $searchService, EntityManagerInterface $em, ChambreTypeService $chambreTypeService): Response
{
    $user = $em->getRepository(User::class)->find(14);
    if (!$user) {
        throw $this->createNotFoundException('Utilisateur non trouvé');
    }

    $search = $request->query->get('search');
    $status = $request->query->get('status');
    $sort = $request->query->get('sort', 'date_desc');

    $result = $searchService->getFilteredReservationsByUser($user, $search, $status, $sort, 1, 50);

    return $this->render('front/reservationlog/index.html.twig', [
        'reservations' => $result['reservations'],
        'chambreTypeService' => $chambreTypeService,
    ]);
}

    #[Route('/reservation/delete/{id}', name: 'app_front_reservation_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
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
        
        return $this->json(['success' => true]);
    }

    #[Route('/reservation/qrcode/{id}', name: 'app_front_reservation_qrcode', methods: ['GET'])]
    public function qrcode(int $id, EntityManagerInterface $em, QrCodeService $qrCodeService): Response
    {
        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getUser()->getId() !== 14) {
            throw $this->createNotFoundException();
        }
        
        $content = "Réservation \n";
        $content .= "Logement: " . $reservation->getLogement()->getNom() . "\n";
        $content .= "Arrivée: " . $reservation->getDateDebut()->format('d/m/Y') . "\n";
        $content .= "Départ: " . $reservation->getDateFin()->format('d/m/Y') . "\n";
        $content .= "Montant: " . number_format($reservation->getMontant(), 2, ',', ' ') . " DT\n";
        $content .= "Modalité: " . $reservation->getModalites();
        
        $qrCodeDataUri = $qrCodeService->generateQrCodeDataUri($content);
        
        $html = '
        <div class="text-center">
            <img src="' . $qrCodeDataUri . '" class="img-fluid mb-3" style="max-width: 250px;">
            <h5>Réservation </h5>
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