<?php

namespace App\Controller\Client;

use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Repository\VehiculeRepository;
use App\Service\EmailService;
use App\Service\WeatherService;
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
            $data   = $request->request->all();
            $errors = [];

            // ── Nom complet ──────────────────────────────
            $nomComplet = trim($data['nom_complet'] ?? '');
            if ($nomComplet === '') {
                $errors['nom_complet'] = 'Le nom complet est obligatoire.';
            } elseif (strlen($nomComplet) < 3) {
                $errors['nom_complet'] = 'Le nom complet doit contenir au moins 3 caractères.';
            } elseif (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $nomComplet)) {
                $errors['nom_complet'] = 'Le nom complet ne doit contenir que des lettres.';
            }

            // ── Téléphone ────────────────────────────────
            $telephone = trim($data['telephone'] ?? '');
            if ($telephone === '') {
                $errors['telephone'] = 'Le téléphone est obligatoire.';
            } elseif (!preg_match('/^[\+\d\s]{8,15}$/', $telephone)) {
                $errors['telephone'] = 'Le téléphone doit contenir entre 8 et 15 chiffres.';
            }

            // ── CIN / Passeport ──────────────────────────
            $cin = trim($data['cin'] ?? '');
            if ($cin === '') {
                $errors['cin'] = 'Le CIN ou numéro de passeport est obligatoire.';
            } elseif (!preg_match('/^[A-Z0-9]{6,20}$/i', $cin)) {
                $errors['cin'] = 'Format invalide (6 à 20 caractères alphanumériques).';
            }

            // ── Email (facultatif mais validé si renseigné) ──
            $email = trim($data['email'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'adresse email n\'est pas valide.';
            }

            // ── Dates ────────────────────────────────────
            $dateDebutStr = trim($data['date_debut'] ?? '');
            $dateFinStr   = trim($data['date_fin']   ?? '');
            $debut        = null;
            $fin          = null;

            if ($dateDebutStr === '') {
                $errors['date_debut'] = 'La date de début est obligatoire.';
            } else {
                try {
                    $debut = new DateTime($dateDebutStr);
                    $aujourd_hui = new DateTime('today');
                    if ($debut < $aujourd_hui) {
                        $errors['date_debut'] = 'La date de début ne peut pas être dans le passé.';
                    }
                } catch (\Exception) {
                    $errors['date_debut'] = 'Date de début invalide.';
                }
            }

            if ($dateFinStr === '') {
                $errors['date_fin'] = 'La date de fin est obligatoire.';
            } else {
                try {
                    $fin = new DateTime($dateFinStr);
                    if ($debut && $fin <= $debut) {
                        $errors['date_fin'] = 'La date de fin doit être après la date de début.';
                    }
                } catch (\Exception) {
                    $errors['date_fin'] = 'Date de fin invalide.';
                }
            }

            // ── Conditions générales ─────────────────────
            if (empty($data['conditions'])) {
                $errors['conditions'] = 'Vous devez accepter les conditions générales.';
            }

            // ── Si erreurs → retourner le formulaire ─────
            if (!empty($errors)) {
                return $this->render('client/reservation.html.twig', [
                    'vehicule'   => $vehicule,
                    'errors'     => $errors,
                    'data'       => $data,
                    'date_debut' => $dateDebutStr,
                    'date_fin'   => $dateFinStr,
                ]);
            }

            // ── Création de la location ──────────────────
            $location = new Location();
            $location->setVehicule($vehicule);
            $location->setClientNomComplet($nomComplet);
            $location->setClientTelephone($telephone);
            $location->setClientCin($cin);
            $location->setClientAdresse($data['adresse'] ?? '');
            $location->setNotes($data['notes'] ?? '');
            $location->setDateDebut($debut);
            $location->setDateFinPrevue($fin);

            $jours  = max(1, (int) $debut->diff($fin)->days);
            $extras = [];
            if (!empty($data['gps']))        $extras['gps']                       = 10.0;
            if (!empty($data['siege_bebe'])) $extras['siege_bebe']                = 15.0;
            if (!empty($data['assurance']))  $extras['assurance_complementaire']  = 25.0;

            $montantExtras = array_sum($extras);
            $montantBase   = $jours * (float) $vehicule->getPrixParJour();
            $montantTotal  = round($montantBase + $montantExtras, 3);
            $avance        = round($montantTotal * 0.30, 3);

            $location->setExtras(empty($extras) ? null : $extras);
            $location->setMontantTotal((string) $montantTotal);
            $location->setAvance((string) $avance);
            $location->setKilometrageDebut($vehicule->getKilometrage() ?? 0);
            $location->setStatut('réservée');
            $location->setPrixParJour((string) $vehicule->getPrixParJour());

            // Marquer le véhicule comme loué
            $vehicule->setEtat('louee');
            $em->persist($vehicule);
            $em->persist($location);
            $em->flush();

            // Email de confirmation (silencieux si échec)
            if (!empty($email)) {
                try {
                    $emailService->envoyerConfirmationLocation($location, $email);
                } catch (\Exception) {
                    // ne pas bloquer la confirmation
                }
            }

            return $this->redirectToRoute('client_confirmation', ['id' => $location->getIdLocation()]);
        }

        // ── GET : afficher le formulaire vide ────────────
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
                    $result      = $emailService->envoyerConfirmationLocation($location, $email);
                    $emailEnvoye = $result['succes'] ?? false;
                } catch (\Exception) {
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
        LocationRepository $locationRepo,
        WeatherService $weatherService
    ): Response {
        $vehicules  = $vehiculeRepo->findAll();
        $vehiculeId = $request->query->get('vehicule_id', $vehicules[0]?->getIdVehicule());
        $mois       = (int) $request->query->get('mois', date('n'));
        $annee      = (int) $request->query->get('annee', date('Y'));

        // ── Jours occupés pour le véhicule sélectionné (calendrier) ──
        $joursOccupes = [];
        if ($vehiculeId) {
            $locsVehicule = $locationRepo->createQueryBuilder('l')
                ->where('l.vehicule = :vid')
                ->andWhere('l.statut NOT IN (:statuts)')
                ->setParameter('vid', $vehiculeId)
                ->setParameter('statuts', ['annulée', 'no_show'])
                ->getQuery()
                ->getResult();

            foreach ($locsVehicule as $loc) {
                $debutLoc = $loc->getDateDebut();
                $finLoc   = $loc->getDateFinPrevue() ?? $loc->getDateFinReelle();
                if (!$debutLoc || !$finLoc) continue;

                $current = clone $debutLoc;
                while ($current <= $finLoc) {
                    if ((int)$current->format('n') === $mois && (int)$current->format('Y') === $annee) {
                        $joursOccupes[] = (int)$current->format('j');
                    }
                    $current->modify('+1 day');
                }
            }
        }

        // ── Occupation par véhicule (vue cartes + gantt) ──
        $occupationParVehicule = [];
        $toutesLocations = $locationRepo->createQueryBuilder('l')
            ->andWhere('l.statut NOT IN (:statuts)')
            ->setParameter('statuts', ['annulée', 'no_show'])
            ->getQuery()
            ->getResult();

        foreach ($toutesLocations as $loc) {
            $vid      = $loc->getVehicule()?->getIdVehicule();
            $debutLoc = $loc->getDateDebut();
            $finLoc   = $loc->getDateFinPrevue() ?? $loc->getDateFinReelle();
            if (!$vid || !$debutLoc || !$finLoc) continue;

            if (!isset($occupationParVehicule[$vid])) {
                $occupationParVehicule[$vid] = [];
            }

            $current = clone $debutLoc;
            while ($current <= $finLoc) {
                if ((int)$current->format('n') === $mois && (int)$current->format('Y') === $annee) {
                    $occupationParVehicule[$vid][] = (int)$current->format('j');
                }
                $current->modify('+1 day');
            }
        }

        // Dédoublonner chaque liste
        foreach ($occupationParVehicule as $vid => $jours) {
            $occupationParVehicule[$vid] = array_values(array_unique($jours));
        }

        // ── Stats ──
        $nbDisponibles = count($vehiculeRepo->findBy(['etat' => 'disponible']));
        $nbLoues       = count($vehiculeRepo->findBy(['etat' => 'louee']));
        $nbTotal       = count($vehicules);
        $nbJoursMois   = (int) date('t', mktime(0, 0, 0, $mois, 1, $annee));
        $tauxOccupation = $nbTotal > 0 ? (int) round(($nbLoues / $nbTotal) * 100) : 0;

        // ── Météo actuelle (silencieuse si API indisponible) ──
        $meteo = null;
        try {
            $meteo = $weatherService->getMeteoActuelle();
        } catch (\Exception) {}

        // ── Prévisions 5 jours (silencieuses si API indisponible) ──
        $previsions = [];
        try {
            $previsions = $weatherService->getPrevisions5Jours();
        } catch (\Exception) {}

        return $this->render('client/planning.html.twig', [
            'vehicules'              => $vehicules,
            'vehicule_id'            => (int) $vehiculeId,
            'mois'                   => $mois,
            'annee'                  => $annee,
            'jours_occupes'          => array_unique($joursOccupes),
            'occupation_par_vehicule' => $occupationParVehicule, // ✅ vue cartes + gantt
            'meteo'                  => $meteo,
            'previsions'             => $previsions,
            'nb_disponibles'         => $nbDisponibles,
            'nb_loues'               => $nbLoues,
            'taux_occupation'        => $tauxOccupation,         // ✅ ajouté
            'nb_jours_mois'          => $nbJoursMois,            // ✅ ajouté
        ]);
    }
}