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
public function index(Request $request, EntityManagerInterface $entityManager): Response
{
    $destination = trim((string) $request->query->get('destination', ''));
    $dateDepart = trim((string) $request->query->get('date_depart', ''));
    $dateRetour = trim((string) $request->query->get('date_retour', ''));
    $budgetMax = trim((string) $request->query->get('budget_max', ''));

    $qb = $entityManager->getRepository(Voyage::class)->createQueryBuilder('v');

    if ($destination !== '') {
        $qb->andWhere('LOWER(v.destination) LIKE :destination')
           ->setParameter('destination', '%' . strtolower($destination) . '%');
    }

    if ($dateDepart !== '') {
        try {
            $dateDepartObj = new \DateTime($dateDepart);
            $qb->andWhere('v.dateDepart >= :dateDepart')
               ->setParameter('dateDepart', $dateDepartObj);
        } catch (\Exception $e) {
        }
    }

    if ($dateRetour !== '') {
        try {
            $dateRetourObj = new \DateTime($dateRetour);
            $qb->andWhere('v.dateRetour <= :dateRetour')
               ->setParameter('dateRetour', $dateRetourObj);
        } catch (\Exception $e) {
        }
    }

    if ($budgetMax !== '' && is_numeric($budgetMax)) {
        $qb->andWhere('v.prix <= :budgetMax')
           ->setParameter('budgetMax', (float) $budgetMax);
    }

    $voyagesFiltres = $qb
        ->orderBy('v.id', 'DESC')
        ->setMaxResults(12)
        ->getQuery()
        ->getResult();

    $voyagesPopulaires = $entityManager->createQueryBuilder()
        ->select('v, COUNT(r.id) AS HIDDEN nbReservations')
        ->from(Voyage::class, 'v')
        ->leftJoin(Reservation::class, 'r', 'WITH', 'r.voyage = v')
        ->groupBy('v.id')
        ->orderBy('nbReservations', 'DESC')
        ->addOrderBy('v.id', 'DESC')
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();

    return $this->render('front/index.html.twig', [
        'voyages' => $voyagesFiltres,
        'voyagesPopulaires' => $voyagesPopulaires,
        'filters' => [
            'destination' => $destination,
            'date_depart' => $dateDepart,
            'date_retour' => $dateRetour,
            'budget_max' => $budgetMax,
        ],
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
    EntityManagerInterface $entityManager,
    \Symfony\Component\Validator\Validator\ValidatorInterface $validator
): Response {
    $voyage = $doctrine->getRepository(Voyage::class)->find($id);

    if (!$voyage) {
        throw $this->createNotFoundException('Voyage introuvable.');
    }

    if ($voyage->getPlacesRestantes() <= 0) {
        $this->addFlash('error', 'Désolé, ce voyage est complet.');
        return $this->redirectToRoute('app_front_voyage_detail', ['id' => $voyage->getId()]);
    }

    $errors = [];
    $formData = [
        'nom' => '',
        'prenom' => '',
        'email' => '',
        'nb_personnes' => 1,
    ];

    if ($request->isMethod('POST')) {
        $formData['nom'] = trim((string) $request->request->get('nom'));
        $formData['prenom'] = trim((string) $request->request->get('prenom'));
        $formData['email'] = trim((string) $request->request->get('email'));
        $formData['nb_personnes'] = (int) $request->request->get('nb_personnes', 1);

        $constraints = new \Symfony\Component\Validator\Constraints\Collection([
            'nom' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'Le nom est obligatoire.'
                ]),
                new \Symfony\Component\Validator\Constraints\Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Le nom doit contenir au moins 2 caractères.',
                    'maxMessage' => 'Le nom ne doit pas dépasser 50 caractères.'
                ]),
                new \Symfony\Component\Validator\Constraints\Regex([
                    'pattern' => '/^[\p{L}\s\'-]+$/u',
                    'message' => 'Le nom ne doit contenir que des lettres.'
                ]),
            ],
            'prenom' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'Le prénom est obligatoire.'
                ]),
                new \Symfony\Component\Validator\Constraints\Length([
                    'min' => 2,
                    'max' => 50,
                    'minMessage' => 'Le prénom doit contenir au moins 2 caractères.',
                    'maxMessage' => 'Le prénom ne doit pas dépasser 50 caractères.'
                ]),
                new \Symfony\Component\Validator\Constraints\Regex([
                    'pattern' => '/^[\p{L}\s\'-]+$/u',
                    'message' => 'Le prénom ne doit contenir que des lettres.'
                ]),
            ],
            'email' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'L’email est obligatoire.'
                ]),
                new \Symfony\Component\Validator\Constraints\Length([
                    'max' => 180,
                    'maxMessage' => 'L’email ne doit pas dépasser 180 caractères.'
                ]),
                new \Symfony\Component\Validator\Constraints\Email([
                    'message' => 'Veuillez saisir une adresse email valide.'
                ]),
            ],
            'nb_personnes' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'Le nombre de personnes est obligatoire.'
                ]),
                new \Symfony\Component\Validator\Constraints\Positive([
                    'message' => 'Le nombre de personnes doit être supérieur à 0.'
                ]),
            ],
        ]);

        $violations = $validator->validate($formData, $constraints);

        if ($formData['nb_personnes'] > $voyage->getPlacesRestantes()) {
            $errors['nb_personnes'][] = 'Le nombre de places demandées dépasse les places restantes.';
        }

        foreach ($violations as $violation) {
            $field = str_replace(['[', ']'], '', $violation->getPropertyPath());
            $errors[$field][] = $violation->getMessage();
        }

        if (empty($errors)) {
            $reservation = new Reservation();
            $reservation->setVoyage($voyage);
            $reservation->setNbrPersonnes($formData['nb_personnes']);
            $reservation->setDateReservation(new \DateTime());
            $reservation->setStatut('EN_ATTENTE');

            if ($this->getUser()) {
                $reservation->setUser($this->getUser());
            }

            $prixTotal = $voyage->getPrix() * $formData['nb_personnes'];

            if (method_exists($reservation, 'setPrixTotal')) {
                $reservation->setPrixTotal($prixTotal);
            }

            $voyage->setPlacesRestantes(
                $voyage->getPlacesRestantes() - $formData['nb_personnes']
            );

            $entityManager->persist($reservation);
            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a bien été enregistrée avec succès.');

            return $this->redirectToRoute('app_front_voyage_detail', [
                'id' => $voyage->getId()
            ]);
        }
    }

    return $this->render('front/reservation.html.twig', [
        'voyage' => $voyage,
        'errors' => $errors,
        'formData' => $formData,
    ]);
}


    #[Route('/mes-reservations', name: 'app_front_mes_reservations')]
public function mesReservations(
    Request $request,
    ManagerRegistry $doctrine
): Response {
    $statut = trim((string) $request->query->get('statut', ''));

    $qb = $doctrine->getRepository(Reservation::class)->createQueryBuilder('r')
        ->leftJoin('r.voyage', 'v')
        ->addSelect('v')
        ->orderBy('r.dateReservation', 'DESC');

    if ($this->getUser()) {
        $qb->andWhere('r.user = :user')
           ->setParameter('user', $this->getUser());
    }

    if ($statut !== '') {
        $qb->andWhere('r.statut = :statut')
           ->setParameter('statut', $statut);
    }

    $reservations = $qb->getQuery()->getResult();

    return $this->render('front/mes_reservations.html.twig', [
        'reservations' => $reservations,
        'selectedStatut' => $statut,
    ]);
}

#[Route('/reservation/{id}/annuler', name: 'app_front_annuler_reservation', methods: ['POST'])]
public function annulerReservation(
    int $id,
    Request $request,
    ManagerRegistry $doctrine,
    EntityManagerInterface $entityManager
): Response {
    $reservation = $doctrine->getRepository(Reservation::class)->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException('Réservation introuvable.');
    }

    if (!$this->isCsrfTokenValid('annuler_reservation_' . $reservation->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Jeton CSRF invalide.');
        return $this->redirectToRoute('app_front_mes_reservations');
    }

    if ($reservation->getStatut() === 'CONFIRMEE') {
        $this->addFlash('error', 'Une réservation confirmée ne peut pas être annulée.');
        return $this->redirectToRoute('app_front_mes_reservations');
    }

    if ($reservation->getStatut() === 'ANNULEE') {
        $this->addFlash('error', 'Cette réservation est déjà annulée.');
        return $this->redirectToRoute('app_front_mes_reservations');
    }

    $voyage = $reservation->getVoyage();

    if ($voyage) {
        $voyage->setPlacesRestantes(
            $voyage->getPlacesRestantes() + $reservation->getNbrPersonnes()
        );
        $entityManager->persist($voyage);
    }

    $reservation->setStatut('ANNULEE');
    $entityManager->persist($reservation);
    $entityManager->flush();

    $this->addFlash('success', 'La réservation a été annulée avec succès.');

    return $this->redirectToRoute('app_front_mes_reservations');
}

    #[Route('/reservation/{id}/modifier', name: 'app_front_modifier_reservation', requirements: ['id' => '\d+'])]
    public function modifierReservation(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        EntityManagerInterface $entityManager
    ): Response {
        $reservation = $doctrine->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        $user = $this->getUser();
        if (!$user || $reservation->getUser() !== $user) {
            $this->addFlash('error', 'Vous n’êtes pas autorisé à modifier cette réservation.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        if ($reservation->getStatut() === 'ANNULEE') {
            $this->addFlash('error', 'Impossible de modifier une réservation annulée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        if ($reservation->getStatut() === 'CONFIRMEE') {
            $this->addFlash('error', 'Une réservation confirmée ne peut pas être modifiée.');
            return $this->redirectToRoute('app_front_mes_reservations');
        }

        $voyage = $reservation->getVoyage();

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        if ($request->isMethod('POST')) {
            $nouveauNbr = (int) $request->request->get('nb_personnes', 1);
            $ancienNbr = $reservation->getNbrPersonnes();

            if ($nouveauNbr < 1) {
                $this->addFlash('error', 'Le nombre de personnes doit être supérieur à 0.');
                return $this->redirectToRoute('app_front_modifier_reservation', ['id' => $reservation->getId()]);
            }

            $difference = $nouveauNbr - $ancienNbr;

            if ($difference > 0 && $difference > $voyage->getPlacesRestantes()) {
                $this->addFlash('error', 'Le nombre demandé dépasse les places restantes disponibles.');
                return $this->redirectToRoute('app_front_modifier_reservation', ['id' => $reservation->getId()]);
            }

            $reservation->setNbrPersonnes($nouveauNbr);
            $voyage->setPlacesRestantes($voyage->getPlacesRestantes() - $difference);

            $entityManager->persist($reservation);
            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a bien été modifiée.');

            return $this->redirectToRoute('app_front_mes_reservations');
        }

        return $this->render('front/modifier_reservation.html.twig', [
            'reservation' => $reservation,
            'voyage' => $voyage,
        ]);
    }

 #[Route('/voyages', name: 'app_front_voyages')]
public function voyages(Request $request, EntityManagerInterface $entityManager): Response
{
    $destination = trim((string) $request->query->get('destination', ''));
    $dateDepart = trim((string) $request->query->get('date_depart', ''));
    $dateRetour = trim((string) $request->query->get('date_retour', ''));
    $budgetMax = trim((string) $request->query->get('budget_max', ''));

    $qb = $entityManager->getRepository(Voyage::class)->createQueryBuilder('v');

    // DESTINATION
    if ($destination !== '') {
        $qb->andWhere('LOWER(v.destination) LIKE :destination')
           ->setParameter('destination', '%' . strtolower($destination) . '%');
    }

    // DATE DEPART
    if ($dateDepart !== '') {
        try {
            $dateDepartObj = new \DateTime($dateDepart);
            $qb->andWhere('v.dateDepart >= :dateDepart')
               ->setParameter('dateDepart', $dateDepartObj);
        } catch (\Exception $e) {}
    }

    // DATE RETOUR
    if ($dateRetour !== '') {
        try {
            $dateRetourObj = new \DateTime($dateRetour);
            $qb->andWhere('v.dateRetour <= :dateRetour')
               ->setParameter('dateRetour', $dateRetourObj);
        } catch (\Exception $e) {}
    }

    // BUDGET
    if ($budgetMax !== '' && is_numeric($budgetMax)) {
        $qb->andWhere('v.prix <= :budgetMax')
           ->setParameter('budgetMax', (float) $budgetMax);
    }

    $voyages = $qb
        ->orderBy('v.dateDepart', 'ASC')
        ->getQuery()
        ->getResult();

    return $this->render('front/voyages.html.twig', [
        'voyages' => $voyages,
        'filters' => [
            'destination' => $destination,
            'date_depart' => $dateDepart,
            'date_retour' => $dateRetour,
            'budget_max' => $budgetMax,
        ],
    ]);
}
}