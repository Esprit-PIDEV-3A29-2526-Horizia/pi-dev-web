<?php

namespace App\Controller;

use App\Entity\Voyage;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'app_front_home')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $voyages = $doctrine->getRepository(Voyage::class)->findBy([], ['id' => 'DESC'], 6);

        return $this->render('front/index.html.twig', [
            'voyages' => $voyages,
        ]);
    }

    #[Route('/voyage/{id}', name: 'app_front_voyage_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id, ManagerRegistry $doctrine): Response
    {
        $voyage = $doctrine->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        return $this->render('front/detail.html.twig', [
            'voyage' => $voyage,
        ]);
    }

    #[Route('/voyage/{id}/reserver', name: 'app_front_reserver_voyage', requirements: ['id' => '\d+'])]
    public function reserver(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        EntityManagerInterface $entityManager
    ): Response {
        $voyage = $doctrine->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        if ($voyage->getPlacesRestantes() <= 0) {
            $this->addFlash('error', 'Désolé, ce voyage est complet.');
            return $this->redirectToRoute('app_front_voyage_detail', ['id' => $voyage->getId()]);
        }

        if ($request->isMethod('POST')) {
            $nbPersonnes = (int) $request->request->get('nb_personnes', 1);

            if ($nbPersonnes < 1) {
                $this->addFlash('error', 'Le nombre de personnes doit être supérieur à 0.');
                return $this->redirectToRoute('app_front_reserver_voyage', ['id' => $voyage->getId()]);
            }

            if ($nbPersonnes > $voyage->getPlacesRestantes()) {
                $this->addFlash('error', 'Le nombre de places demandées dépasse les places restantes.');
                return $this->redirectToRoute('app_front_reserver_voyage', ['id' => $voyage->getId()]);
            }

            $reservation = new Reservation();
            $reservation->setVoyage($voyage);
            $reservation->setNbrPersonnes($nbPersonnes);
            $reservation->setDateReservation(new \DateTime());
            $reservation->setStatut('EN_ATTENTE');

            if ($this->getUser()) {
                $reservation->setUser($this->getUser());
            }

            $voyage->setPlacesRestantes($voyage->getPlacesRestantes() - $nbPersonnes);

            $entityManager->persist($reservation);
            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a bien été enregistrée avec succès.');

            return $this->redirectToRoute('app_front_voyage_detail', [
                'id' => $voyage->getId()
            ]);
        }

        return $this->render('front/reservation.html.twig', [
            'voyage' => $voyage,
        ]);
    }
}