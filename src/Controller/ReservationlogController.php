<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservationlog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationlogController extends AbstractController
{
    #[Route('/logement/{id}/reserver', name: 'app_front_reservation_new')]
    public function new(Request $request, Logement $logement, EntityManagerInterface $em): Response
    {
        $userId = 4; // User statique pour test

        if ($request->isMethod('POST')) {
            $dateArrivee = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_arrivee'));
            $dateDepart = \DateTime::createFromFormat('Y-m-d', $request->request->get('date_depart'));
            $adultes = (int)$request->request->get('adultes', 1);
            $enfants = (int)$request->request->get('enfants', 0);
            $modalite = $request->request->get('modalite');

            $errors = [];
            if (!$dateArrivee || !$dateDepart) {
                $errors[] = 'Dates invalides.';
            } elseif ($dateArrivee < new \DateTime() || $dateDepart <= $dateArrivee) {
                $errors[] = 'Dates invalides (départ après arrivée, futur uniquement).';
            }

            if ($adultes + $enfants > $logement->getCapacite()) {
                $errors[] = 'Capacité dépassée.';
            }

            if (empty($errors)) {
                $nuits = $dateArrivee->diff($dateDepart)->days;
                $montant = $nuits * $logement->getTarifNuit();

                $reservation = new Reservationlog();
                $reservation->setLogement($logement);
                $user = $em->getRepository(User::class)->find($userId);
                if (!$user) {
                    throw $this->createNotFoundException('User test non trouvé.');
                }
                $reservation->setUser($user);
                $reservation->setDateDebut($dateArrivee);
                $reservation->setDateFin($dateDepart);
                $reservation->setMontant($montant);
                $reservation->setStatus('en_attente');
                $reservation->setModalites($modalite);

                $em->persist($reservation);
                $em->flush();

                $this->addFlash('success', 'Réservation créée !');
                return $this->redirectToRoute('app_home');
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('front/reservationlog/form.html.twig', [
            'logement' => $logement,
        ]);
    }
}
