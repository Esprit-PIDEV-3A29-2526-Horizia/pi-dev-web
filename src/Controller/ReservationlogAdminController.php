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

#[Route('/admin/reservationlog', name: 'admin_reservation_')]
class ReservationlogAdminController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ReservationlogSearchService $searchService,
        ReservationlogRepository $reservationlogRepository
    ): Response {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $sort   = $request->query->get('sort', '');
        $page   = max(1, $request->query->getInt('page', 1));
        $limit  = 10; // Nombre d'éléments par page

        // Utilisation du service pour obtenir les réservations filtrées + paginées
        $result = $searchService->getFilteredReservations($search, $status, $sort, $page, $limit);
        $reservations = $result['reservations'];
        $total = $result['total'];

        // Nombre total de réservations (sans filtre) pour le badge
        $totalReservations = $reservationlogRepository->count([]);

        return $this->render('admin/reservationlog_admin/index.html.twig', [
            'reservationlogs'   => $reservations,
            'total'             => $total,
            'totalReservations' => $totalReservations,
            'currentPage'       => $page,
            'search'            => $search,
            'status'            => $status,
            'sort'              => $sort,
            'limit'             => $limit, // ← AJOUT OBLIGATOIRE
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
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
        return $this->render('admin/reservationlog_admin/show.html.twig', [
            'reservation' => $reservationlog,
        ]);
    }

    #[Route('/{idreslog}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservationlog $reservationlog, EntityManagerInterface $entityManager): Response
    {
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
        if ($this->isCsrfTokenValid('delete' . $reservationlog->getIdreslog(), $request->request->get('_token'))) {
            $entityManager->remove($reservationlog);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée.');
        }
        return $this->redirectToRoute('admin_reservation_index');
    }
}