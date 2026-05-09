<?php

namespace App\Controller\Admin;

use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    #[Route('/', name: 'admin_stats_index')]
    public function index(PublicationRepository $pubRepo, CommentaireRepository $comRepo, EntityManagerInterface $em): Response
    {
        // Statistiques générales
        $totalPublications = $pubRepo->count([]);
        $totalLikes = $pubRepo->createQueryBuilder('p')
            ->select('SUM(p.likes)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $totalCommentaires = $comRepo->count([]);

        // Publications par catégorie
        $categories = $pubRepo->createQueryBuilder('p')
            ->select('p.categorie, COUNT(p.id) as count')
            ->groupBy('p.categorie')
            ->getQuery()
            ->getResult();

        // Top 5 des publications les plus likées
        $topPublications = $pubRepo->createQueryBuilder('p')
            ->select('p.titre, p.likes')
            ->orderBy('p.likes', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Évolution mensuelle (requête SQL native)
        $sql = "SELECT YEAR(date_creation) as annee, MONTH(date_creation) as mois, COUNT(id) as total 
                FROM publication 
                GROUP BY YEAR(date_creation), MONTH(date_creation) 
                ORDER BY annee DESC, mois DESC 
                LIMIT 12";
        
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('annee', 'annee');
        $rsm->addScalarResult('mois', 'mois');
        $rsm->addScalarResult('total', 'total');
        
        $query = $em->createNativeQuery($sql, $rsm);
        $evolution = $query->getResult();
        $evolution = array_reverse($evolution); // ordre chronologique

        // Forcer les tableaux même vides
        if (empty($categories)) $categories = [];
        if (empty($topPublications)) $topPublications = [];
        if (empty($evolution)) $evolution = [];

        return $this->render('admin/stats/index.html.twig', [
            'totalPublications' => $totalPublications,
            'totalLikes' => $totalLikes,
            'totalCommentaires' => $totalCommentaires,
            'categories' => $categories,
            'topPublications' => $topPublications,
            'evolution' => $evolution,
        ]);
    }
}