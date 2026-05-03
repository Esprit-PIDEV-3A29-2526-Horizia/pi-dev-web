<?php
// src/Controller/MesReservationslogController.php

namespace App\Controller;

use App\Entity\Reservationlog;
use App\Entity\User;
use App\Service\ChambreTypeService;
use App\Service\EmailService;
use App\Service\PdfService;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MesReservationslogController extends AbstractController
{
    #[Route('/mes-reservations_log', name: 'app_front_reservationlog_index')]
    public function index(): Response
    {
        return $this->render('front/reservationlog/index.html.twig');
    }

    #[Route('/mes-reservations/ajax', name: 'app_front_reservation_ajax', methods: ['GET'])]
    public function ajax(Request $request, EntityManagerInterface $em, ChambreTypeService $chambreTypeService, PaginatorInterface $paginator): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $search = $request->query->get('search', '');
            $status = $request->query->get('status', '');
            $sort   = $request->query->get('sort', 'date_desc');
            $page   = max(1, $request->query->getInt('page', 1));
            $limit  = max(1, $request->query->getInt('limit', 6));

            $qb = $em->getRepository(Reservationlog::class)->createQueryBuilder('r')
                ->leftJoin('r.logement', 'l')
                ->leftJoin('r.user', 'u')
                ->where('r.user = :user')
                ->setParameter('user', $user);

            if ($search !== '') {
                $qb->andWhere('l.nom LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }

            if ($status !== '') {
                $qb->andWhere('r.status = :status')
                   ->setParameter('status', $status);
            }

            if ($status !== 'annulée') {
                $qb->andWhere('r.status != :cancelled')
                   ->setParameter('cancelled', 'annulée');
            }

            switch ($sort) {
                case 'date_asc':     $qb->orderBy('r.date_debut', 'ASC');  break;
                case 'date_desc':    $qb->orderBy('r.date_debut', 'DESC'); break;
                case 'montant_asc':  $qb->orderBy('r.montant', 'ASC');     break;
                case 'montant_desc': $qb->orderBy('r.montant', 'DESC');    break;
                default:             $qb->orderBy('r.idreslog', 'DESC');
            }

            $pagination   = $paginator->paginate($qb, $page, $limit);
            $reservations = $pagination->getItems();
            $total        = $pagination->getTotalItemCount();
            $totalPages   = (int) ceil($total / $limit);

            $session       = $request->getSession();
            $notifications = $session->get('user_notifications_' . $user->getId(), []);
            $session->remove('user_notifications_' . $user->getId());

            $html = '';
            foreach ($reservations as $reservation) {
                if (!$reservation->getLogement()) continue;

                $chambreTypeDesc = $chambreTypeService->getChambreType(
                    $reservation->getAdultes(),
                    $reservation->getEnfants(),
                    $reservation->getNombreChambres()
                );
                $pension      = $reservation->getModeReservation();
                $pensionLabel = $pension ? str_replace('_', ' ', $pension) : '-';

                $chambresDisplay = $chambreTypeDesc;
                if ($reservation->getRepartitionChambres()) {
                    $formatted = $chambreTypeService->formatRepartition($reservation->getRepartitionChambres());
                    if ($formatted) $chambresDisplay = $formatted;
                }

                $badgeClass = match ($reservation->getStatus()) {
                    'confirmée'  => 'confirmed',
                    'en_attente' => 'pending',
                    'terminée'   => 'completed',
                    'expirée'    => 'expired',
                    default      => 'cancelled',
                };

                $createdAt = $reservation->getCreatedAt() ? $reservation->getCreatedAt()->getTimestamp() : 0;
                $montant   = $reservation->getMontant() ?? 0.0;
                $dateDebut = $reservation->getDateDebut();
                $dateFin   = $reservation->getDateFin();

                $html .= '<div class="col-md-6 col-lg-4" data-id="' . $reservation->getIdreslog() . '">
                    <div class="reservation-card">
                        <div class="reservation-card-content">
                            <div class="card-header">
                                <h5>' . htmlspecialchars($reservation->getLogement()->getNom()) . '</h5>
                                <span class="badge-status ' . $badgeClass . '">' . htmlspecialchars($reservation->getStatus() ?? '') . '</span>
                            </div>
                            <div class="card-details">
                                <div class="detail-item"><small><i class="fa fa-calendar"></i> Arrivée</small><p>' . ($dateDebut?->format('d/m/Y') ?? '') . '</p></div>
                                <div class="detail-item"><small><i class="fa fa-calendar"></i> Départ</small><p>' . ($dateFin?->format('d/m/Y') ?? '') . '</p></div>
                                <div class="detail-item"><small><i class="fa fa-money"></i> Montant</small><p>' . number_format($montant, 2, ',', ' ') . ' DT</p></div>
                                <div class="detail-item"><small><i class="fa fa-credit-card"></i> Modalité</small><p>' . htmlspecialchars((string) $reservation->getModalites()) . '</p></div>
                                <div class="detail-item"><small><i class="fa fa-users"></i> Occupants</small><p>' . $reservation->getAdultes() . ' adulte(s) + ' . $reservation->getEnfants() . ' enfant(s)</p></div>
                                <div class="detail-item"><small><i class="fa fa-bed"></i> Chambres</small><p>' . htmlspecialchars($chambresDisplay) . '</p></div>
                                <div class="detail-item"><small><i class="fa fa-cutlery"></i> Pension</small><p>' . htmlspecialchars($pensionLabel) . '</p></div>
                            </div>
                            <div class="card-actions">';

                if ($reservation->getStatus() !== 'terminée') {
                    $html .= '<a href="#" class="btn-action btn-edit" data-id="' . $reservation->getIdreslog() . '"><i class="fa fa-pencil"></i> Modifier</a>';
                    $html .= '<a href="#" class="btn-action btn-delete" data-id="' . $reservation->getIdreslog() . '" data-created-at="' . $createdAt . '"><i class="fa fa-trash"></i> Supprimer</a>';
                }
                if ($reservation->getStatus() == 'confirmée') {
                    $html .= '<a href="#" class="btn-action btn-qr" data-id="' . $reservation->getIdreslog() . '"><i class="fa fa-qrcode"></i> QR Code</a>';
                }
                if ($reservation->getStatus() == 'en_attente' && $reservation->getModalites() == 'En ligne') {
                    try {
                        $payUrl = $this->generateUrl('app_front_reservation_pay', ['id' => $reservation->getIdreslog()]);
                        $html .= '<a href="' . $payUrl . '" class="btn-action btn-pay" style="background: #23779C; color:white;"><i class="fa fa-credit-card"></i> Finaliser paiement</a>';
                    } catch (\Exception $e) {}
                }

                $html .= '</div></div></div></div>';
            }

            if (empty($reservations)) {
                $html = '<div class="col-12"><div class="text-center py-5 bg-light rounded-4"><i class="bi bi-calendar-x display-1 text-muted mb-4 d-block"></i><h3 class="text-muted mb-3">Aucune réservation</h3><p class="text-muted mb-4">Commencez par réserver un logement.</p><a href="' . $this->generateUrl('app_front_logement_index') . '" class="btn btn-horozia-primary btn-lg px-5 rounded-pill"><i class="bi bi-house-door me-2"></i> Découvrir les logements</a></div></div>';
            }

            return $this->json([
                'html'          => $html,
                'notifications' => $notifications,
                'currentPage'   => $page,
                'totalPages'    => $totalPages,
                'total'         => $total,
            ]);
        } catch (\Exception $e) {
            error_log('ERREUR AJAX RESERVATIONS: ' . $e->getMessage() . ' dans ' . $e->getFile() . ':' . $e->getLine());
            return $this->json([
                'html'          => '<div class="col-12 text-center text-danger">Erreur technique : ' . $e->getMessage() . '</div>',
                'notifications' => [],
                'currentPage'   => 1,
                'totalPages'    => 0,
                'total'         => 0,
            ], 500);
        }
    }

    #[Route('/reservation/delete/{id}', name: 'app_front_reservation_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }

        $resUser = $reservation->getUser();
        if ($resUser === null || $resUser->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $now       = new \DateTime();
        $createdAt = $reservation->getCreatedAt();
        if ($createdAt === null) {
            return $this->json(['success' => false, 'error' => 'Date de création introuvable.'], 400);
        }
        $ageEnHeures = ($now->getTimestamp() - $createdAt->getTimestamp()) / 3600;

        if ($ageEnHeures >= 1) {
            return $this->json(['success' => false, 'error' => 'Cette réservation a plus d\'1 heure, utilisez la demande d\'annulation.'], 400);
        }

        $em->remove($reservation);
        $em->flush();
        $emailService->sendCancellationEmail(
            (string) $resUser->getEmail(),
            $reservation,
            'Suppression par l\'utilisateur (moins d\'1h)'
        );
        return $this->json(['success' => true, 'message' => 'Réservation supprimée.']);
    }

    #[Route('/reservation/request-cancel/{id}', name: 'app_front_reservation_request_cancel', methods: ['POST'])]
    public function requestCancel(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        $resUser     = $reservation?->getUser();
        if (!$reservation || $resUser === null || $resUser->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }

        if (!in_array($reservation->getStatus(), ['confirmée', 'en_attente'])) {
            return $this->json(['success' => false, 'error' => 'Seules les réservations confirmées ou en attente peuvent être annulées.'], 400);
        }

        $reservation->setStatus('demande_annulation');
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Demande d\'annulation envoyée à l\'administrateur.']);
    }

    #[Route('/reservation/qrcode/{id}', name: 'app_front_reservation_qrcode', methods: ['GET'])]
    public function qrcode(int $id, EntityManagerInterface $em, QrCodeService $qrCodeService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        $resUser     = $reservation?->getUser();
        if (!$reservation || $resUser === null || $resUser->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }

        $logement  = $reservation->getLogement();
        $dateDebut = $reservation->getDateDebut();
        $dateFin   = $reservation->getDateFin();
        $montant   = $reservation->getMontant() ?? 0.0;

        $content  = "Logement: " . ($logement?->getNom() ?? '') . "\n";
        $content .= "Arrivée: " . ($dateDebut?->format('d/m/Y') ?? '') . "\n";
        $content .= "Départ: " . ($dateFin?->format('d/m/Y') ?? '') . "\n";
        $content .= "Adultes: " . $reservation->getAdultes() . "\n";
        $content .= "Enfants: " . $reservation->getEnfants() . "\n";
        $content .= "Chambres: " . $reservation->getNombreChambres() . "\n";
        $content .= "Pension: " . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . "\n";
        $content .= "Montant: " . number_format($montant, 2, ',', ' ') . " DT\n";
        $content .= "Modalité: " . $reservation->getModalites() . "\n";
        $content .= "Statut: " . $reservation->getStatus();

        $qrCodeDataUri = $qrCodeService->generateQrCodeBase64($content);

        $html = '
        <div class="text-center">
            <img src="' . $qrCodeDataUri . '" class="img-fluid mb-3" style="max-width: 250px;">
            <h5>' . htmlspecialchars($logement?->getNom() ?? '') . '</h5>
            <p>' . ($dateDebut?->format('d/m/Y') ?? '') . ' → ' . ($dateFin?->format('d/m/Y') ?? '') . '</p>
            <p>👥 ' . $reservation->getAdultes() . ' adulte(s) + ' . $reservation->getEnfants() . ' enfant(s)</p>
            <p>🛏️ ' . $reservation->getNombreChambres() . ' chambre(s)</p>
            <p>🍽️ ' . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . '</p>
            <p>💰 ' . number_format($montant, 2, ',', ' ') . ' DT</p>
            <p>💳 ' . htmlspecialchars((string) $reservation->getModalites()) . '</p>
            <p>📌 ' . htmlspecialchars($reservation->getStatus() ?? '') . '</p>
            <a href="' . $this->generateUrl('app_front_reservation_download_pdf', ['id' => $reservation->getIdreslog()]) . '" class="btn btn-primary mt-2" target="_blank">
                <i class="fa fa-file-pdf"></i> Télécharger la réservation (PDF)
            </a>
        </div>';

        return new Response($html);
    }

    #[Route('/reservation/download-pdf/{id}', name: 'app_front_reservation_download_pdf', methods: ['GET'])]
    public function downloadPdf(int $id, EntityManagerInterface $em, PdfService $pdfService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        $resUser     = $reservation?->getUser();
        if (!$reservation || $resUser === null || $resUser->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }

        $pdfContent = $pdfService->generateReservationPdf($reservation);

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reservation.pdf"',
        ]);
    }

    #[Route('/reservation/send-email/{id}', name: 'app_front_reservation_send_email', methods: ['POST'])]
    public function sendEmail(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        $resUser     = $reservation?->getUser();
        if (!$reservation || $resUser === null || $resUser->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }

        try {
            $customMessage = "✅ Votre réservation a bien été enregistrée.";
            $success = $emailService->sendReservationEmail(
                (string) $resUser->getEmail(),
                $reservation,
                $customMessage
            );

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
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        $resUser     = $reservation?->getUser();
        if (!$reservation || $resUser === null || $resUser->getId() !== $user->getId()) {
            throw $this->createNotFoundException();
        }
        $logement = $reservation->getLogement();

        return $this->render('front/reservationlog/edit.html.twig', [
            'reservation' => $reservation,
            'logement'    => $logement,
        ]);
    }

    #[Route('/reservation/edit/{id}', name: 'app_front_reservation_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
        }

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        $resUser     = $reservation?->getUser();
        if (!$reservation || $resUser === null || $resUser->getId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Réservation non trouvée'], 404);
        }
        $logement = $reservation->getLogement();

        $dateArrivee         = \DateTime::createFromFormat('Y-m-d', (string) $request->request->get('date_arrivee'));
        $dateDepart          = \DateTime::createFromFormat('Y-m-d', (string) $request->request->get('date_depart'));
        $modalite            = (string) $request->request->get('modalite');
        $adultes             = (int) $request->request->get('adultes', 1);
        $enfants             = (int) $request->request->get('enfants', 0);
        $nombreChambres      = (int) $request->request->get('nombre_chambres', 1);
        $modeReservation     = (string) $request->request->get('mode_reservation');
        $repartitionChambres = $request->request->get('repartition_chambres');

        $typeLogement = strtolower((string) $logement?->getType());
        $isHotel = ($typeLogement === 'hôtel' || $typeLogement === 'hotel');
        if (!$isHotel) {
            $nombreChambres = 1;
        }

        $errors = [];
        if (!$dateArrivee || !$dateDepart) {
            $errors[] = 'Dates invalides.';
        } elseif ($dateArrivee < new \DateTime() || $dateDepart <= $dateArrivee) {
            $errors[] = 'Les dates doivent être valides (départ après arrivée, et non passées).';
        }
        if ($adultes < 1)    $errors[] = 'Au moins 1 adulte.';
        if ($enfants < 0)    $errors[] = 'Nombre d\'enfants invalide.';
        if ($nombreChambres < 1) $errors[] = 'Au moins 1 chambre.';
        if (empty($modeReservation) || !in_array($modeReservation, ['all_inclusive', 'all_inclusive_soft(sans_alcool)', 'demi_pension', 'logement_petit_dejeuner'])) {
            $errors[] = 'Formule de pension invalide.';
        }

        if (!empty($errors)) {
            return $this->json(['success' => false, 'error' => implode(' ', $errors)]);
        }

        /** @var \DateTime $dateArrivee */
        /** @var \DateTime $dateDepart */
        $nuits               = $dateArrivee->diff($dateDepart)->days;
        $nombrePersonnes     = $adultes + $enfants;
        $prixBase            = (float) ($logement?->getTarifNuit() ?? 0);
        $coefficient         = $this->getPensionCoefficient($modeReservation);
        $montant             = $nuits * $nombrePersonnes * $prixBase * $coefficient;

        $reservation->setDateDebut($dateArrivee);
        $reservation->setDateFin($dateDepart);
        $reservation->setMontant($montant);
        $reservation->setModalites($modalite);
        $reservation->setAdultes($adultes);
        $reservation->setEnfants($enfants);
        $reservation->setNombreChambres($nombreChambres);
        $reservation->setModeReservation($modeReservation);
        if ($repartitionChambres !== null) {
            $reservation->setRepartitionChambres((string) $repartitionChambres);
        }
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Réservation modifiée avec succès']);
    }

    private function getPensionCoefficient(?string $modeReservation): float
    {
        return match ($modeReservation) {
            'demi_pension'                    => 1.20,
            'all_inclusive'                   => 1.45,
            'all_inclusive_soft(sans_alcool)' => 1.37,
            'logement_petit_dejeuner'         => 1.00,
            default                           => 1.00,
        };
    }
}