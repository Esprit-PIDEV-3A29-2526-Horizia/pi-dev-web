<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    public function index(EntityManagerInterface $entityManager): Response
    {
        $nbVoyages = $entityManager->getRepository(Voyage::class)->count([]);
        $nbReservations = $entityManager->getRepository(Reservation::class)->count([]);
        $nbUsers = $entityManager->getRepository(User::class)->count([]);
        $nbCategories = $entityManager->getRepository(Categorie::class)->count([]);

        $nbReservationsConfirmees = $entityManager->getRepository(Reservation::class)->count(['statut' => 'CONFIRMEE']);
        $nbReservationsEnAttente = $entityManager->getRepository(Reservation::class)->count(['statut' => 'EN_ATTENTE']);
        $nbReservationsAnnulees = $entityManager->getRepository(Reservation::class)->count(['statut' => 'ANNULEE']);

        $recentReservations = $entityManager->getRepository(Reservation::class)->findBy([], ['id' => 'DESC'], 5);
        $recentVoyages = $entityManager->getRepository(Voyage::class)->findBy([], ['id' => 'DESC'], 5);

        $voyages = $entityManager->getRepository(Voyage::class)->findAll();
        $nbVoyagesPlacesFaibles = 0;

        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Accès interdit.');
            return $this->redirectToRoute('app_logout');
        }

        foreach ($voyages as $voyage) {
            if (method_exists($voyage, 'getPlacesRestantes') && $voyage->getPlacesRestantes() !== null && $voyage->getPlacesRestantes() <= 10) {
                $nbVoyagesPlacesFaibles++;
            }
        }

        $chartLabels = ['Voyages', 'Réservations', 'Utilisateurs', 'Catégories'];
        $chartData = [$nbVoyages, $nbReservations, $nbUsers, $nbCategories];

        $lineChartLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'];
        $lineChartData = [5, 8, 12, 10, 15, 18];

        return $this->render('admin/dashboard.html.twig', [
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
            'nbReservationsConfirmees' => $nbReservationsConfirmees,
            'nbReservationsEnAttente' => $nbReservationsEnAttente,
            'nbReservationsAnnulees' => $nbReservationsAnnulees,
            'nbVoyagesPlacesFaibles' => $nbVoyagesPlacesFaibles,
            'recentReservations' => $recentReservations,
            'recentVoyages' => $recentVoyages,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'lineChartLabels' => $lineChartLabels,
            'lineChartData' => $lineChartData,
        ]);
    }
}