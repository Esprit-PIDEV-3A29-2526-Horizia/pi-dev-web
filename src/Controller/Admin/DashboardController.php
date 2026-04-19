<?php

namespace App\Controller\Admin;

use App\Entity\Publication;
use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $em): Response
    {
        // Totaux
        $totalPublications = $em->getRepository(Publication::class)->count([]);
        $totalCommentaires = $em->getRepository(Commentaire::class)->count([]);
        $totalLikes = $em->getRepository(Publication::class)
            ->createQueryBuilder('p')
            ->select('SUM(p.likes)')
            ->getQuery()
            ->getSingleScalarResult();

        // Top 5 publications les plus likées
        $topPublications = $em->getRepository(Publication::class)
            ->findBy([], ['likes' => 'DESC'], 5);

        // 5 dernières publications
        $dernieresActivites = $em->getRepository(Publication::class)
            ->findBy([], ['date_creation' => 'DESC'], 5);

        return $this->render('admin/dashboard/index.html.twig', [
            'totalPublications' => $totalPublications,
            'totalCommentaires' => $totalCommentaires,
            'totalLikes' => $totalLikes,
            'topPublications' => $topPublications,
            'dernieresActivites' => $dernieresActivites,
        ]);
    }
}