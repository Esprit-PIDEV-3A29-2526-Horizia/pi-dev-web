<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Voyage;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $reservations = $entityManager->getRepository(Reservation::class)->findAll();

        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $voyage = $entityManager->getRepository(Voyage::class)->find($reservation->getIdVoyage());

            if (!$voyage) {
                $form->get('idVoyage')->addError(new FormError('Le voyage sélectionné est introuvable.'));
            } elseif ($reservation->getNbrPersonnes() > $voyage->getPlacesRestantes()) {
                $form->get('nbrPersonnes')->addError(new FormError('Le nombre de personnes dépasse les places restantes du voyage.'));
            } else {
                $voyage->setPlacesRestantes($voyage->getPlacesRestantes() - $reservation->getNbrPersonnes());

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

    #[Route('/edit/{id}', name: 'app_reservation_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $ancienIdVoyage = $reservation->getIdVoyage();
        $ancienNbrPersonnes = $reservation->getNbrPersonnes();

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauVoyage = $entityManager->getRepository(Voyage::class)->find($reservation->getIdVoyage());

            if (!$nouveauVoyage) {
                $form->get('idVoyage')->addError(new FormError('Le voyage sélectionné est introuvable.'));
            } else {
                if ($ancienIdVoyage === $reservation->getIdVoyage()) {
                    $difference = $reservation->getNbrPersonnes() - $ancienNbrPersonnes;

                    if ($difference > 0 && $difference > $nouveauVoyage->getPlacesRestantes()) {
                        $form->get('nbrPersonnes')->addError(new FormError('Le nombre de personnes dépasse les places restantes du voyage.'));
                    } else {
                        $nouveauVoyage->setPlacesRestantes($nouveauVoyage->getPlacesRestantes() - $difference);

                        $entityManager->flush();

                        $this->addFlash('success', 'Réservation modifiée avec succès.');

                        return $this->redirectToRoute('app_reservation_index');
                    }
                } else {
                    $ancienVoyage = $entityManager->getRepository(Voyage::class)->find($ancienIdVoyage);

                    if ($ancienVoyage) {
                        $ancienVoyage->setPlacesRestantes($ancienVoyage->getPlacesRestantes() + $ancienNbrPersonnes);
                    }

                    if ($reservation->getNbrPersonnes() > $nouveauVoyage->getPlacesRestantes()) {
                        $form->get('nbrPersonnes')->addError(new FormError('Le nombre de personnes dépasse les places restantes du nouveau voyage.'));
                    } else {
                        $nouveauVoyage->setPlacesRestantes($nouveauVoyage->getPlacesRestantes() - $reservation->getNbrPersonnes());

                        $entityManager->flush();

                        $this->addFlash('success', 'Réservation modifiée avec succès.');

                        return $this->redirectToRoute('app_reservation_index');
                    }
                }
            }
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_reservation_delete')]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $voyage = $entityManager->getRepository(Voyage::class)->find($reservation->getIdVoyage());

        if ($voyage) {
            $voyage->setPlacesRestantes($voyage->getPlacesRestantes() + $reservation->getNbrPersonnes());
        }

        $entityManager->remove($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation supprimée avec succès.');

        return $this->redirectToRoute('app_reservation_index');
    }
}