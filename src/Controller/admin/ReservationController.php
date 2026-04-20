<?php

namespace App\Controller\admin;

use App\Entity\Reservation;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin/reservation')]
class ReservationController extends AbstractController
{

     /**
     * Vérifie que l'utilisateur a les droits d'admin
     */
    private function checkAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }



    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
     ): Response {
        $search = trim((string) $request->query->get('search', ''));
        $sort = (string) $request->query->get('sort', '');

        $qb = $entityManager->createQueryBuilder()
            ->select('r', 'v', 'u')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('r.user', 'u');

        if ($search !== '') {
            $qb->andWhere(
                'LOWER(r.statut) LIKE :search
                 OR LOWER(v.titre) LIKE :search
                 OR LOWER(COALESCE(u.nom, \'\')) LIKE :search
                 OR LOWER(COALESCE(u.prenom, \'\')) LIKE :search'
            )->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('r.dateReservation', 'ASC');
                break;
            case 'date_desc':
                $qb->orderBy('r.dateReservation', 'DESC');
                break;
            case 'statut_asc':
                $qb->orderBy('r.statut', 'ASC');
                break;
            case 'nbr_asc':
                $qb->orderBy('r.nbrPersonnes', 'ASC');
                break;
            case 'nbr_desc':
                $qb->orderBy('r.nbrPersonnes', 'DESC');
                break;
            default:
                $qb->orderBy('r.id', 'DESC');
                break;
        }

        $reservations = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $reservation->recalculerNbrPersonnes();

            if ($form->isValid()) {
                $entityManager->persist($reservation);
                $entityManager->flush();

                $this->addFlash('success', 'Réservation ajoutée avec succès.');
                return $this->redirectToRoute('app_reservation_index');
            }
        }

        return $this->render('admin/reservation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_reservation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        if ($reservation->getStatut() === 'CONFIRMEE') {
            $this->addFlash('error', 'Une réservation confirmée ne peut pas être modifiée.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $reservation->recalculerNbrPersonnes();

            if ($form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Réservation modifiée avec succès.');
                return $this->redirectToRoute('app_reservation_index');
            }
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_reservation_delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        if ($reservation->getStatut() === 'CONFIRMEE' && $reservation->getVoyage()) {
            $voyage = $reservation->getVoyage();
            $voyage->setPlacesRestantes($voyage->getPlacesRestantes() + $reservation->getNbrPersonnes());
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation supprimée avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}/confirmer', name: 'app_reservation_confirmer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function confirmer(
        int $id,
        EntityManagerInterface $entityManager,
        \App\Service\BrevoMailerService $brevoMailerService,
        UrlGeneratorInterface $urlGenerator
     ): Response {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        if ($reservation->getStatut() === 'CONFIRMEE') {
            $this->addFlash('info', 'Cette réservation est déjà confirmée.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($reservation->getStatut() === 'ANNULEE') {
            $this->addFlash('error', 'Une réservation annulée ne peut pas être confirmée.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $voyage = $reservation->getVoyage();

        if (!$voyage) {
            $this->addFlash('error', 'Aucun voyage associé à cette réservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($reservation->getNbrPersonnes() <= 0) {
            $this->addFlash('error', 'Le nombre de personnes est invalide.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($reservation->getNbrPersonnes() > $voyage->getPlacesRestantes()) {
            $this->addFlash('error', 'Places insuffisantes pour confirmer cette réservation.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $voyage->setPlacesRestantes(
            $voyage->getPlacesRestantes() - $reservation->getNbrPersonnes()
        );

        $reservation->setStatut('CONFIRMEE');
        $entityManager->flush();

        $user = $reservation->getUser();

        if (!$user || !method_exists($user, 'getEmail') || !$user->getEmail()) {
            $this->addFlash('warning', 'Réservation confirmée, mais aucun email utilisateur n’est disponible.');
            return $this->redirectToRoute('app_reservation_index');
        }

        try {
            $fullName = '';

            if (method_exists($user, 'getPrenom') && method_exists($user, 'getNom')) {
                $fullName = trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? ''));
            } elseif (method_exists($user, 'getNom')) {
                $fullName = (string) ($user->getNom() ?? '');
            }

            $paymentUrl = $urlGenerator->generate('app_payment_checkout', [
                'id' => $reservation->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $brevoMailerService->sendReservationConfirmation(
                $user->getEmail(),
                $fullName !== '' ? $fullName : 'Client',
                (string) $voyage->getTitre(),
                (string) $voyage->getDestination(),
                $voyage->getDateDepart()?->format('d/m/Y') ?? '',
                $voyage->getDateRetour()?->format('d/m/Y') ?? '',
                (int) $reservation->getNbrPersonnes(),
                $reservation->getId(),
                $paymentUrl
            );

            $this->addFlash('success', 'Réservation confirmée avec succès et email envoyé à ' . $user->getEmail());
        } catch (\Throwable $e) {
            $this->addFlash('warning', 'Réservation confirmée, mais email non envoyé : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->createQueryBuilder()
            ->select('r', 'v', 'u')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('r.user', 'u')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_reservation_annuler', methods: ['POST'])]
    public function annuler(
        int $id,
        EntityManagerInterface $entityManager,
        \App\Service\BrevoMailerService $brevoMailerService
     ): Response {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        if ($reservation->getStatut() === 'ANNULEE') {
            $this->addFlash('info', 'Cette réservation est déjà annulée.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $voyage = $reservation->getVoyage();

        if ($reservation->getStatut() === 'CONFIRMEE' && $voyage) {
            $voyage->setPlacesRestantes(
                $voyage->getPlacesRestantes() + $reservation->getNbrPersonnes()
            );
        }

        $reservation->setStatut('ANNULEE');
        $entityManager->flush();

        $user = $reservation->getUser();

        if ($user && method_exists($user, 'getEmail') && $user->getEmail()) {
            try {
                $fullName = '';

                if (method_exists($user, 'getPrenom') && method_exists($user, 'getNom')) {
                    $fullName = trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? ''));
                } elseif (method_exists($user, 'getNom')) {
                    $fullName = (string) ($user->getNom() ?? '');
                }

                if ($voyage) {
                    $brevoMailerService->sendReservationCancellation(
                        $user->getEmail(),
                        $fullName !== '' ? $fullName : 'Client',
                        (string) $voyage->getTitre(),
                        (string) $voyage->getDestination(),
                        $voyage->getDateDepart()?->format('d/m/Y') ?? '',
                        $voyage->getDateRetour()?->format('d/m/Y') ?? '',
                        (int) $reservation->getNbrPersonnes(),
                        $reservation->getId()
                    );
                }
            } catch (\Throwable $e) {
                $this->addFlash('warning', 'Réservation annulée, mais email non envoyé : ' . $e->getMessage());
                return $this->redirectToRoute('app_reservation_index');
            }
        }

        $this->addFlash('success', 'Réservation annulée avec succès.');

        return $this->redirectToRoute('app_reservation_index');
    }

    
}