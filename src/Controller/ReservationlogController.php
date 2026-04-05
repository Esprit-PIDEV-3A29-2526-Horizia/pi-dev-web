<?php

namespace App\Controller;

use App\Entity\Reservationlog;
use App\Entity\User;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationlogController extends AbstractController
{
    #[Route('/logement/{id}/reserver-modal', name: 'app_front_reservation_modal', methods: ['GET'])]
    public function reservationModal(int $id, LogementRepository $logementRepository): Response
    {
        $logement = $logementRepository->find($id);
        if (!$logement) {
            throw $this->createNotFoundException('Logement non trouvé');
        }
        return $this->render('front/reservationlog/_form_modal.html.twig', [
            'logement' => $logement,
        ]);
    }

    #[Route('/logement/{id}/reserver', name: 'app_front_reservation_new')]
    public function new(Request $request, int $id, LogementRepository $logementRepository, EntityManagerInterface $em): Response
    {
        $logement = $logementRepository->find($id);
        if (!$logement) {
            throw $this->createNotFoundException('Logement non trouvé');
        }

        // Utilisateur temporaire (ID 1 – à remplacer par $this->getUser())
        $user = $em->getRepository(User::class)->find(1);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur de test non trouvé.');
            return $this->redirectToRoute('app_front_logement_index');
        }

        if ($request->isMethod('POST')) {
            $dateArrivee = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_arrivee'));
            $dateDepart  = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_depart'));
            $adultes     = (int)$request->request->get('adultes', 1);
            $enfants     = (int)$request->request->get('enfants', 0);
            $modalite    = $request->request->get('modalite');

            $errors = [];
            if (!$dateArrivee || !$dateDepart) {
                $errors[] = 'Dates invalides.';
            } elseif ($dateArrivee < new \DateTime() || $dateDepart <= $dateArrivee) {
                $errors[] = 'Les dates doivent être valides (départ après arrivée, et non passées).';
            }
            if ($adultes + $enfants > $logement->getCapacite()) {
                $errors[] = 'Le nombre total de personnes dépasse la capacité du logement.';
            }

            if (empty($errors)) {
                $nuits = $dateArrivee->diff($dateDepart)->days;
                $montant = $nuits * $logement->getTarifNuit();

                $reservation = new Reservationlog();
                $reservation->setLogement($logement);
                $reservation->setUser($user);
                $reservation->setDateDebut($dateArrivee);
                $reservation->setDateFin($dateDepart);
                $reservation->setMontant($montant);
                $reservation->setStatus('en_attente');
                $reservation->setModalites($modalite);

                $em->persist($reservation);
                $em->flush();

                $this->addFlash('success', 'Réservation enregistrée avec succès !');
                return $this->redirectToRoute('app_front_logement_index');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->redirectToRoute('app_front_logement_index');
    }
}