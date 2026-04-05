<?php

namespace App\Controller\Client;

use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Repository\VehiculeRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;

#[Route('/client')]
class ClientController extends AbstractController
{
    // ══════════════════════════════════════════
    // ACCUEIL CLIENT
    // ══════════════════════════════════════════
    #[Route('/', name: 'client_accueil', methods: ['GET'])]
    public function accueil(VehiculeRepository $vehiculeRepo, LocationRepository $locationRepo): Response
    {
        $nbDisponibles = count($vehiculeRepo->findBy(['etat' => 'disponible']));
        $nbActives     = count($locationRepo->findBy(['statut' => 'en_cours']));

        return $this->render('client/accueil.html.twig', [
            'nb_disponibles' => $nbDisponibles,
            'nb_actives'     => $nbActives,
        ]);
    }

    // ══════════════════════════════════════════
    // CATALOGUE VOITURES
    // ══════════════════════════════════════════
    #[Route('/catalogue', name: 'client_catalogue', methods: ['GET'])]
    public function catalogue(
        Request $request,
        VehiculeRepository $vehiculeRepo
    ): Response {
        $dateDebut = $request->query->get('date_debut');
        $dateFin   = $request->query->get('date_fin');
        $type      = $request->query->get('type', 'Tous les types');

        $vehicules = $vehiculeRepo->findBy(['etat' => 'disponible']);

        return $this->render('client/catalogue.html.twig', [
            'vehicules'  => $vehicules,
            'date_debut' => $dateDebut,
            'date_fin'   => $dateFin,
            'type'       => $type,
        ]);
    }

    // ══════════════════════════════════════════
    // RÉSERVATION
    // ══════════════════════════════════════════
    #[Route('/reservation/{id}', name: 'client_reservation', methods: ['GET', 'POST'])]
    public function reservation(
        int $id,
        Request $request,
        VehiculeRepository $vehiculeRepo,
        LocationRepository $locationRepo,
        EntityManagerInterface $em,
        EmailService $emailService
    ): Response {
        $vehicule = $vehiculeRepo->find($id);
        if (!$vehicule) {
            throw $this->createNotFoundException('Véhicule introuvable.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            // Validation de base
            $errors = [];
            if (empty($data['nom_complet']))  $errors[] = 'Nom complet requis.';
            if (empty($data['telephone']))    $errors[] = 'Téléphone requis.';
            if (empty($data['cin']))          $errors[] = 'CIN / Passport requis.';
            if (empty($data['date_debut']))   $errors[] = 'Date de début requise.';
            if (empty($data['date_fin']))     $errors[] = 'Date de fin requise.';
            if (empty($data['conditions']))   $errors[] = 'Vous devez accepter les conditions.';

            if (!empty($errors)) {
                return $this->render('client/reservation.html.twig', [
                    'vehicule'   => $vehicule,
                    'errors'     => $errors,
                    'data'       => $data,
                    'date_debut' => $data['date_debut'] ?? null,
                    'date_fin'   => $data['date_fin']   ?? null,
                ]);
            }

            // Création location
            $location = new Location();
            $location->setVehicule($vehicule);
            $location->setClientNomComplet($data['nom_complet']);
            $location->setClientTelephone($data['telephone']);
            $location->setClientCin($data['cin']);
            $location->setClientAdresse($data['adresse'] ?? '');
            $location->setNotes($data['notes'] ?? '');

            $debut = new DateTime($data['date_debut']);
            $fin   = new DateTime($data['date_fin']);
            $location->setDateDebut($debut);
            $location->setDateFinPrevue($fin);

            $jours = max(1, (int) $debut->diff($fin)->days);
            $extras = [];
            if (!empty($data['gps']))         $extras['gps']         = 10.0;
            if (!empty($data['siege_bebe']))   $extras['siege_bebe']  = 15.0;
            if (!empty($data['assurance']))    $extras['assurance_complementaire'] = 25.0;

            $montantExtras = array_sum($extras);
            $montantBase   = $jours * $vehicule->getPrixParJour();
            $montantTotal  = $montantBase + $montantExtras;
            $avance        = round($montantTotal * 0.30, 3);

            $location->setExtras(empty($extras) ? null : $extras);
            $location->setMontantTotal($montantTotal);
            $location->setAvance($avance);
            $location->setKilometrageDebut($vehicule->getKilometrage() ?? 0);
            $location->setStatut('réservée');
            $location->setPrixParJour($vehicule->getPrixParJour());

            // Marquer le véhicule comme loué
            $vehicule->setEtat('louee');
            $em->persist($vehicule);
            $em->persist($location);
            $em->flush();

            // Email de confirmation
            $emailClient = trim($data['email'] ?? '');
            if (!empty($emailClient)) {
                try {
                    $emailService->envoyerConfirmationLocation($location, $emailClient);
                } catch (\Exception $e) {
                    // silencieux — ne pas bloquer la confirmation
                }
            }

            return $this->redirectToRoute('client_confirmation', ['id' => $location->getIdLocation()]);
        }

        return $this->render('client/reservation.html.twig', [
            'vehicule'   => $vehicule,
            'date_debut' => $request->query->get('date_debut'),
            'date_fin'   => $request->query->get('date_fin'),
            'errors'     => [],
            'data'       => [],
        ]);
    }

    // ══════════════════════════════════════════
    // CONFIRMATION
    // ══════════════════════════════════════════
    #[Route('/confirmation/{id}', name: 'client_confirmation', methods: ['GET', 'POST'])]
    public function confirmation(
        int $id,
        Request $request,
        LocationRepository $locationRepo,
        EmailService $emailService
    ): Response {
        $location = $locationRepo->find($id);
        if (!$location) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $emailEnvoye = null;
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    $result = $emailService->envoyerConfirmationLocation($location, $email);
                    $emailEnvoye = $result['succes'] ?? false;
                } catch (\Exception $e) {
                    $emailEnvoye = false;
                }
            }
        }

        return $this->render('client/confirmation.html.twig', [
            'location'     => $location,
            'email_envoye' => $emailEnvoye,
        ]);
    }

    // ══════════════════════════════════════════
    // MES RÉSERVATIONS (recherche par CIN)
    // ══════════════════════════════════════════
    #[Route('/mes-reservations', name: 'client_mes_reservations', methods: ['GET'])]
    public function mesReservations(
        Request $request,
        LocationRepository $locationRepo
    ): Response {
        $cin       = trim($request->query->get('cin', ''));
        $locations = [];
        $recherche = false;

        if (!empty($cin)) {
            $recherche = true;
            $locations = $locationRepo->findBy(['clientCin' => $cin], ['dateDebut' => 'DESC']);
        }

        return $this->render('client/mes_reservations.html.twig', [
            'locations' => $locations,
            'cin'       => $cin,
            'recherche' => $recherche,
        ]);
    }

    // ══════════════════════════════════════════
    // PLANNING DES DISPONIBILITÉS
    // ══════════════════════════════════════════
    #[Route('/planning', name: 'client_planning', methods: ['GET'])]
    public function planning(
        Request $request,
        VehiculeRepository $vehiculeRepo,
        LocationRepository $locationRepo
    ): Response {
        $vehicules  = $vehiculeRepo->findAll();
        $vehiculeId = $request->query->get('vehicule_id', $vehicules[0]?->getIdVehicule());
        $mois       = (int) $request->query->get('mois', date('n'));
        $annee      = (int) $request->query->get('annee', date('Y'));

        // Calcul des jours occupés pour ce véhicule/mois
        $joursOccupes = [];
        if ($vehiculeId) {
            $locations = $locationRepo->createQueryBuilder('l')
                ->where('l.vehicule = :vid')
                ->andWhere('l.statut NOT IN (:statuts)')
                ->setParameter('vid', $vehiculeId)
                ->setParameter('statuts', ['annulée', 'no_show'])
                ->getQuery()
                ->getResult();

            foreach ($locations as $loc) {
                $debut = $loc->getDateDebut();
                $fin   = $loc->getDateFinPrevue() ?? $loc->getDateFinReelle();
                if (!$debut || !$fin) continue;

                $current = clone $debut;
                while ($current <= $fin) {
                    if ((int)$current->format('n') === $mois && (int)$current->format('Y') === $annee) {
                        $joursOccupes[] = (int)$current->format('j');
                    }
                    $current->modify('+1 day');
                }
            }
        }

        return $this->render('client/planning.html.twig', [
            'vehicules'     => $vehicules,
            'vehicule_id'   => (int) $vehiculeId,
            'mois'          => $mois,
            'annee'         => $annee,
            'jours_occupes' => array_unique($joursOccupes),
        ]);
    }
}