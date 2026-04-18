<?php

namespace App\Controller\admin;

use App\Entity\Reservation;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
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
                 OR LOWER(u.nom) LIKE :search
                 OR LOWER(u.prenom) LIKE :search'
            )
            ->setParameter('search', '%' . mb_strtolower($search) . '%');
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

        $reservations = $qb->getQuery()->getResult();

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

        $form = $this->createForm(ReservationType::class, $reservation, [
            'places_restantes' => 9999
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $voyage = $reservation->getVoyage();

            if (!$voyage) {
                $this->addFlash('error', 'Veuillez choisir un voyage.');
                return $this->render('admin/reservation/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            if ($reservation->getNbrPersonnes() > $voyage->getPlacesRestantes()) {
                $this->addFlash('error', 'Le nombre de personnes ne peut pas dépasser les places disponibles.');

                return $this->render('admin/reservation/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $voyage->setPlacesRestantes(
                $voyage->getPlacesRestantes() - $reservation->getNbrPersonnes()
            );

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Réservation ajoutée avec succès.');
            return $this->redirectToRoute('app_reservation_index');
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

        $ancienVoyage = $reservation->getVoyage();
        $ancienNbrPersonnes = $reservation->getNbrPersonnes();

        $placesRestantes = 9999;
        if ($ancienVoyage) {
            $placesRestantes = $ancienVoyage->getPlacesRestantes() + $ancienNbrPersonnes;
        }

        $form = $this->createForm(ReservationType::class, $reservation, [
            'places_restantes' => $placesRestantes
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauVoyage = $reservation->getVoyage();
            $nouveauNbrPersonnes = $reservation->getNbrPersonnes();

            if (!$nouveauVoyage) {
                $this->addFlash('error', 'Veuillez choisir un voyage.');
                return $this->render('admin/reservation/edit.html.twig', [
                    'form' => $form->createView(),
                    'reservation' => $reservation,
                ]);
            }

            // Cas 1 : même voyage
            if ($ancienVoyage && $nouveauVoyage->getId() === $ancienVoyage->getId()) {
                $difference = $nouveauNbrPersonnes - $ancienNbrPersonnes;

                if ($difference > 0 && $difference > $ancienVoyage->getPlacesRestantes()) {
                    $this->addFlash('error', 'Le nombre de personnes ne peut pas dépasser les places disponibles.');

                    return $this->render('admin/reservation/edit.html.twig', [
                        'form' => $form->createView(),
                        'reservation' => $reservation,
                    ]);
                }

                $ancienVoyage->setPlacesRestantes(
                    $ancienVoyage->getPlacesRestantes() - $difference
                );
            } else {
                // Cas 2 : changement de voyage
                if ($ancienVoyage) {
                    $ancienVoyage->setPlacesRestantes(
                        $ancienVoyage->getPlacesRestantes() + $ancienNbrPersonnes
                    );
                }

                if ($nouveauNbrPersonnes > $nouveauVoyage->getPlacesRestantes()) {
                    $this->addFlash('error', 'Le nombre de personnes ne peut pas dépasser les places disponibles.');

                    return $this->render('admin/reservation/edit.html.twig', [
                        'form' => $form->createView(),
                        'reservation' => $reservation,
                    ]);
                }

                $nouveauVoyage->setPlacesRestantes(
                    $nouveauVoyage->getPlacesRestantes() - $nouveauNbrPersonnes
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Réservation modifiée avec succès.');
            return $this->redirectToRoute('app_reservation_index');
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

        $voyage = $reservation->getVoyage();

        if ($voyage) {
            $voyage->setPlacesRestantes(
                $voyage->getPlacesRestantes() + $reservation->getNbrPersonnes()
            );
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation supprimée avec succès.');
        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/{id}/confirmer', name: 'app_reservation_confirmer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function confirmer(int $id, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $reservation->setStatut('CONFIRMEE');
        $entityManager->flush();

        $this->addFlash('success', 'Réservation confirmée avec succès.');

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
}