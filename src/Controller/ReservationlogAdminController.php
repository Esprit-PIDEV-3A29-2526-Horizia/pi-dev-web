<?php
namespace App\Controller;

use App\Entity\Reservationlog;
use App\Form\ReservationlogType;
use App\Repository\ReservationlogRepository;
use App\Service\ReservationlogSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin/reservationlog', name: 'admin_reservation_')]
class ReservationlogAdminController extends AbstractController
{
    private function checkAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ReservationlogSearchService $searchService,
        ReservationlogRepository $reservationlogRepository
    ): Response {
        $this->checkAdminAccess();

        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $sort   = $request->query->get('sort', '');
        $page   = max(1, $request->query->getInt('page', 1));
        $limit  = 10;

        $result = $searchService->getFilteredReservations($search, $status, $sort, $page, $limit);
        $reservations = $result['reservations'];
        $total = $result['total'];
        $totalReservations = $reservationlogRepository->count([]);

        return $this->render('admin/reservationlog_admin/index.html.twig', [
            'reservationlogs'   => $reservations,
            'total'             => $total,
            'totalReservations' => $totalReservations,
            'currentPage'       => $page,
            'search'            => $search,
            'status'            => $status,
            'sort'              => $sort,
            'limit'             => $limit,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $reservationlog = new Reservationlog();
        $form = $this->createForm(ReservationlogType::class, $reservationlog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservationlog);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation créée avec succès.');
            return $this->redirectToRoute('admin_reservation_index');
        }

        return $this->render('admin/reservationlog_admin/new.html.twig', [
            'reservation' => $reservationlog,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idreslog}', name: 'show', methods: ['GET'])]
    public function show(Reservationlog $reservationlog): Response
    {
        $this->checkAdminAccess();

        return $this->render('admin/reservationlog_admin/show.html.twig', [
            'reservation' => $reservationlog,
        ]);
    }

    #[Route('/{idreslog}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservationlog $reservationlog, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $form = $this->createForm(ReservationlogType::class, $reservationlog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation modifiée avec succès.');
            return $this->redirectToRoute('admin_reservation_index');
        }

        return $this->render('admin/reservationlog_admin/edit.html.twig', [
            'reservation' => $reservationlog,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idreslog}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Reservationlog $reservationlog, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        if ($this->isCsrfTokenValid('delete' . $reservationlog->getIdreslog(), $request->request->get('_token'))) {
            $entityManager->remove($reservationlog);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée.');
        }
        return $this->redirectToRoute('admin_reservation_index');
    }

    #[Route('/booked-dates', name: 'booked_dates', methods: ['GET'])]
    public function getBookedDates(EntityManagerInterface $em): JsonResponse
    {
        $this->checkAdminAccess();

        $reservations = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.status NOT IN (:excluded)')
            ->setParameter('excluded', ['annulée', 'expirée'])
            ->getQuery()
            ->getResult();

        $events = [];
        foreach ($reservations as $res) {
            $start = $res->getDateDebut()->format('Y-m-d');
            $end = (clone $res->getDateFin())->modify('+1 day')->format('Y-m-d');
            $title = $res->getLogement()->getNom();
            $color = match($res->getStatus()) {
                'confirmée' => '#81AE8D',
                'en_attente' => '#E8B156',
                'terminée' => '#6c757d',
                default => '#dc3545',
            };
            $events[] = [
                'title'  => $title,
                'start'  => $start,
                'end'    => $end,
                'allDay' => true,
                'color'  => $color,
            ];
        }
        return $this->json($events);
    }
}