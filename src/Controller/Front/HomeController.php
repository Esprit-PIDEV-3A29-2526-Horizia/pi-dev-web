<?php

namespace App\Controller\Front;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Reservation;
use App\Entity\Events;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class HomeController extends AbstractController
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

        return $this->render('front/home/index.html.twig', [
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

    // src/Controller/Front/HomeController.php

    #[Route('/home', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_front_home');
    }

    #[Route('/voyage/{id}', name: 'app_front_voyage_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id, ManagerRegistry $doctrine): Response
    {
        $voyage = $doctrine->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        return $this->render('front/voyage/detail.html.twig', [
            'voyage' => $voyage,
        ]);
    }

    #[Route('/voyage/{id}/reserver', name: 'app_front_reserver_voyage', requirements: ['id' => '\d+'])]
    public function reserver(
        int $id,
        Request $request,
        ManagerRegistry $doctrine,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
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

            $constraints = new Assert\Collection([
                'nom' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins 2 caractères.',
                        'maxMessage' => 'Le nom ne doit pas dépasser 50 caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s\'-]+$/u',
                        'message' => 'Le nom ne doit contenir que des lettres.',
                    ]),
                ],
                'prenom' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins 2 caractères.',
                        'maxMessage' => 'Le prénom ne doit pas dépasser 50 caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s\'-]+$/u',
                        'message' => 'Le prénom ne doit contenir que des lettres.',
                    ]),
                ],
                'email' => [
                    new Assert\NotBlank(['message' => 'L’email est obligatoire.']),
                    new Assert\Length([
                        'max' => 180,
                        'maxMessage' => 'L’email ne doit pas dépasser 180 caractères.',
                    ]),
                    new Assert\Email([
                        'message' => 'Veuillez saisir une adresse email valide.',
                    ]),
                ],
                'nb_personnes' => [
                    new Assert\NotBlank(['message' => 'Le nombre de personnes est obligatoire.']),
                    new Assert\Positive(['message' => 'Le nombre de personnes doit être supérieur à 0.']),
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
                    'id' => $voyage->getId(),
                ]);
            }
        }

        return $this->render('front/voyage/reservation.html.twig', [
            'voyage' => $voyage,
            'errors' => $errors,
            'formData' => $formData,
        ]);
    }

    #[Route('/mes-reservations', name: 'app_front_mes_reservations')]
    public function mesReservations(Request $request, EntityManagerInterface $entityManager): Response
    {
        $statut = trim((string) $request->query->get('statut', ''));

        $qb = $entityManager->getRepository(Reservation::class)->createQueryBuilder('r')
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

        return $this->render('front/voyage/mes_reservations.html.twig', [
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

        return $this->render('front/voyage/modifier_reservation.html.twig', [
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

        $voyages = $qb
            ->orderBy('v.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('front/voyage/voyages.html.twig', [
            'voyages' => $voyages,
            'filters' => [
                'destination' => $destination,
                'date_depart' => $dateDepart,
                'date_retour' => $dateRetour,
                'budget_max' => $budgetMax,
            ],
        ]);
    }
    // Ajoutez ces use en haut du fichier

// Ajoutez cette méthode pour la page profil
#[Route('/profile', name: 'app_front_profile')]
public function profile(): Response
{
    $user = $this->getUser();
    
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    return $this->render('front/user/profile.html.twig', [
        'user' => $user,
    ]);
}

// Ajoutez cette méthode pour modifier le profil
#[Route('/profile/edit', name: 'app_profile_edit')]
public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    if ($request->isMethod('POST')) {
        $nom = $request->request->get('nom');
        $prenom = $request->request->get('prenom');
        $telephone = $request->request->get('telephone');
        $addresse = $request->request->get('addresse');
        
        if ($nom) $user->setNom($nom);
        if ($prenom) $user->setPrenom($prenom);
        if ($telephone) $user->setTelephone($telephone);
        if ($addresse) $user->setAddresse($addresse);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Profil modifié avec succès');
        return $this->redirectToRoute('app_front_profile');
    }
    
    return $this->render('front/user/edit_profile.html.twig', [
        'user' => $user,
    ]);
}

// Ajoutez cette méthode pour changer le mot de passe
#[Route('/change-password', name: 'app_front_change_password', methods: ['GET', 'POST'])]
public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    if ($request->isMethod('POST')) {
        $oldPassword = $request->request->get('old_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');
        
        // Vérifier l'ancien mot de passe
        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            $this->addFlash('error', 'Ancien mot de passe incorrect');
            return $this->redirectToRoute('app_front_change_password');
        }
        
        // Vérifier que les nouveaux mots de passe correspondent
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas');
            return $this->redirectToRoute('app_front_change_password');
        }
        
        // Vérifier la longueur du nouveau mot de passe
        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
            return $this->redirectToRoute('app_front_change_password');
        }
        
        // Changer le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès');
        return $this->redirectToRoute('app_front_profile');
    }
    
    return $this->render('front/profile/change_password.html.twig');
}
}