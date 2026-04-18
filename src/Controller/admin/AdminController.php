<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Reservation;
use App\Entity\Reservationlog;
use App\Entity\User;
use App\Entity\Voyage;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $session = $requestStack->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
    }

    private function checkAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user instanceof User || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }

    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $voyageRepository = $entityManager->getRepository(Voyage::class);
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        $userRepository = $entityManager->getRepository(User::class);
        $categorieRepository = $entityManager->getRepository(Categorie::class);

        $nbVoyages = $voyageRepository->count([]);
        $nbReservations = $reservationRepository->count([]);
        $nbUsers = $userRepository->count([]);
        $nbCategories = $categorieRepository->count([]);

        $nbReservationsConfirmees = $reservationRepository->count(['statut' => 'CONFIRMEE']);
        $nbReservationsEnAttente = $reservationRepository->count(['statut' => 'EN_ATTENTE']);
        $nbReservationsAnnulees = $reservationRepository->count(['statut' => 'ANNULEE']);

        $nbVoyagesPlacesFaibles = $entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(Voyage::class, 'v')
            ->where('v.placesRestantes <= :seuil')
            ->setParameter('seuil', 10)
            ->getQuery()
            ->getSingleScalarResult();

        $recentReservations = $entityManager->createQueryBuilder()
            ->select('r')
            ->from(Reservation::class, 'r')
            ->orderBy('r.dateReservation', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $recentVoyages = $entityManager->createQueryBuilder()
            ->select('v')
            ->from(Voyage::class, 'v')
            ->orderBy('v.dateDepart', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $reservationsForChart = $entityManager->createQueryBuilder()
            ->select('r.dateReservation')
            ->from(Reservation::class, 'r')
            ->getQuery()
            ->getResult();

        $monthlyReservations = array_fill(1, 12, 0);

        foreach ($reservationsForChart as $row) {
            if (!empty($row['dateReservation']) && $row['dateReservation'] instanceof \DateTimeInterface) {
                $monthNumber = (int) $row['dateReservation']->format('n');
                $monthlyReservations[$monthNumber]++;
            }
        }

        $pendingCancelRequests = $entityManager->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.idreslog)')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/dashboard.html.twig', [
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
            'nbReservationsConfirmees' => $nbReservationsConfirmees,
            'nbReservationsEnAttente' => $nbReservationsEnAttente,
            'nbReservationsAnnulees' => $nbReservationsAnnulees,
            'nbVoyagesPlacesFaibles' => $nbVoyagesPlacesFaibles,
            'recentReservations' => $recentReservations,
            'recentVoyages' => $recentVoyages,
            'chartLabels' => ['Voyages', 'Réservations', 'Utilisateurs', 'Catégories'],
            'chartData' => [$nbVoyages, $nbReservations, $nbUsers, $nbCategories],
            'lineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'lineChartData' => array_values($monthlyReservations),
            'pendingCancelRequests' => $pendingCancelRequests,
        ]);
    }

    #[Route('/admin/reservations/cancel-requests', name: 'admin_cancel_requests')]
    public function cancelRequests(EntityManagerInterface $em): Response
    {
        $this->checkAdminAccess();

        $requests = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $pendingCancelRequests = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.idreslog)')
            ->where('r.status = :status')
            ->setParameter('status', 'demande_annulation')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/cancel_requests.html.twig', [
            'requests' => $requests,
            'pendingCancelRequests' => $pendingCancelRequests,
        ]);
    }

    #[Route('/admin/reservation/approve-cancel/{id}', name: 'admin_approve_cancel', methods: ['POST'])]
    public function approveCancel(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $this->checkAdminAccess();

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getStatus() !== 'demande_annulation') {
            return $this->json(['error' => 'Réservation non trouvée ou pas en demande d\'annulation'], 404);
        }

        $reservation->setStatus('annulée');
        $em->flush();

        $emailService->sendCancellationApprovedEmail($reservation->getUser()->getEmail(), $reservation);
        $this->addFlash('success', '✅ Annulation approuvée pour la réservation #' . $reservation->getIdreslog());

        $this->requestStack->getSession()->save();

        // Stocker la notification pour l'utilisateur PROPRIÉTAIRE de la réservation (pas l'admin)
        $userSession = $this->requestStack->getSession();
        $userId = $reservation->getUser()->getId();
        $notifications = $userSession->get('user_notifications_' . $userId, []);
        $notifications[] = [
            'message' => '✅ Votre demande d\'annulation pour la réservation #' . $reservation->getIdreslog() . ' a été acceptée.',
            'type' => 'success'
        ];
        $userSession->set('user_notifications_' . $userId, $notifications);
        $userSession->save();

        return $this->json(['success' => true, 'message' => 'Annulation confirmée.']);
    }

    #[Route('/admin/reservation/reject-cancel/{id}', name: 'admin_reject_cancel', methods: ['POST'])]
    public function rejectCancel(int $id, EntityManagerInterface $em, EmailService $emailService): JsonResponse
    {
        $this->checkAdminAccess();

        $reservation = $em->getRepository(Reservationlog::class)->find($id);
        if (!$reservation || $reservation->getStatus() !== 'demande_annulation') {
            return $this->json(['error' => 'Réservation non trouvée ou pas en demande d\'annulation'], 404);
        }

        $reservation->setStatus('confirmée');
        $em->flush();

        $emailService->sendCancellationRejectedEmail($reservation->getUser()->getEmail(), $reservation);
        $this->addFlash('warning', '⚠️ Demande d\'annulation rejetée pour la réservation #' . $reservation->getIdreslog());

        $this->requestStack->getSession()->save();

        // Stocker la notification pour l'utilisateur PROPRIÉTAIRE de la réservation
        $userSession = $this->requestStack->getSession();
        $userId = $reservation->getUser()->getId();
        $notifications = $userSession->get('user_notifications_' . $userId, []);
        $notifications[] = [
            'message' => '❌ Votre demande d\'annulation pour la réservation #' . $reservation->getIdreslog() . ' a été rejetée.',
            'type' => 'error'
        ];
        $userSession->set('user_notifications_' . $userId, $notifications);
        $userSession->save();

        return $this->json(['success' => true, 'message' => 'Demande rejetée.']);
    }
}