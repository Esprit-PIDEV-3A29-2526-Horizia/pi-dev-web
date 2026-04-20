<?php

namespace App\Controller\admin;

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

        $recentReservations = $entityManager->createQueryBuilder()
            ->select('r', 'v', 'u')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('r.user', 'u')
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

        $confirmedReservationsForChart = $entityManager->createQueryBuilder()
            ->select('r.dateReservation')
            ->from(Reservation::class, 'r')
            ->where('r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->getQuery()
            ->getResult();

        $monthlyConfirmedReservations = array_fill(1, 12, 0);

        foreach ($confirmedReservationsForChart as $row) {
            if (!empty($row['dateReservation']) && $row['dateReservation'] instanceof \DateTimeInterface) {
                $monthNumber = (int) $row['dateReservation']->format('n');
                $monthlyConfirmedReservations[$monthNumber]++;
            }
        }

        $topVoyagesRaw = $entityManager->createQueryBuilder()
            ->select('v.titre AS titre', 'COUNT(r.id) AS nbReservations')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->groupBy('v.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $topVoyagesLabels = [];
        $topVoyagesData = [];

        foreach ($topVoyagesRaw as $row) {
            $topVoyagesLabels[] = $row['titre'] ?? 'Voyage';
            $topVoyagesData[] = (int) $row['nbReservations'];
        }

        $topClientsRaw = $entityManager->createQueryBuilder()
            ->select(
                "CONCAT(COALESCE(u.nom, ''), ' ', COALESCE(u.prenom, '')) AS clientNom",
                'COUNT(r.id) AS nbReservations'
            )
            ->from(Reservation::class, 'r')
            ->leftJoin('r.user', 'u')
            ->where('u.id IS NOT NULL')
            ->groupBy('u.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $topClientsLabels = [];
        $topClientsData = [];

        foreach ($topClientsRaw as $row) {
            $nom = trim((string) ($row['clientNom'] ?? ''));
            $topClientsLabels[] = $nom !== '' ? $nom : 'Utilisateur';
            $topClientsData[] = (int) $row['nbReservations'];
        }

        $categoriesStatsRaw = $entityManager->createQueryBuilder()
            ->select('c.nom AS categorieNom', 'COUNT(r.id) AS nbReservations')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('v.categorie', 'c')
            ->groupBy('c.id')
            ->orderBy('nbReservations', 'DESC')
            ->getQuery()
            ->getResult();

        $categoriesLabels = [];
        $categoriesData = [];

        foreach ($categoriesStatsRaw as $row) {
            $categoriesLabels[] = $row['categorieNom'] ?? 'Sans catégorie';
            $categoriesData[] = (int) $row['nbReservations'];
        }

        $confirmedRevenueRows = $entityManager->createQueryBuilder()
            ->select('r', 'v')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->where('r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->getQuery()
            ->getResult();

        $revenuTotalEstime = 0.0;

        foreach ($confirmedRevenueRows as $reservation) {
            $voyage = $reservation->getVoyage();

            if ($voyage) {
                $prixAdulte = (float) $voyage->getPrix();
                $nbAdultes = (int) ($reservation->getNbAdultes() ?? 0);
                $nbEnfants = (int) ($reservation->getNbEnfants() ?? 0);

                $revenuTotalEstime += ($prixAdulte * $nbAdultes) + (($prixAdulte * 0.5) * $nbEnfants);
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
            'nbReservationsConfirmees' => $nbReservationsConfirmees,
            'nbReservationsEnAttente' => $nbReservationsEnAttente,
            'nbReservationsAnnulees' => $nbReservationsAnnulees,
            'recentReservations' => $recentReservations,
            'recentVoyages' => $recentVoyages,
            'revenuTotalEstime' => $revenuTotalEstime,

            'chartLabels' => ['Voyages', 'Réservations', 'Utilisateurs', 'Catégories'],
            'chartData' => [$nbVoyages, $nbReservations, $nbUsers, $nbCategories],

            'lineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'lineChartData' => array_values($monthlyReservations),

            'confirmedLineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'confirmedLineChartData' => array_values($monthlyConfirmedReservations),

            'topVoyagesLabels' => $topVoyagesLabels,
            'topVoyagesData' => $topVoyagesData,

            'topClientsLabels' => $topClientsLabels,
            'topClientsData' => $topClientsData,

            'categoriesLabels' => $categoriesLabels,
            'categoriesData' => $categoriesData,
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