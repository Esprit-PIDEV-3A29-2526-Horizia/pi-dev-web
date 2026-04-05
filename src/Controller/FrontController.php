<?php
namespace App\Controller;
use App\Entity\Logement;
use App\Repository\VoyageRepository;
use App\Service\LogementSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/front/', name: 'app_front_home')]
    public function home(VoyageRepository $voyageRepository): Response
    {
        return $this->render('front/index.html.twig');
    }

    #[Route('/logements', name: 'app_front_logement_index')]
    public function logements(
        Request $request,
        LogementSearchService $searchService,
        EntityManagerInterface $entityManager
    ): Response {
        $search = $request->query->get('q');
        $type   = $request->query->get('type');
        $sort   = $request->query->get('sort');

        $allLogements = $searchService->searchAndSort($search, $type, $sort);
        $logements = array_filter($allLogements, function($logement) {
            return $logement->isDisponibilite() === true;
        });

        $typesDistincts = $entityManager
            ->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->select('DISTINCT l.type')
            ->getQuery()
            ->getScalarResult();
        $typesListe = array_column($typesDistincts, 'type');

        // === AJOUT : variables statiques pour tester la connexion ===
        return $this->render('front/logement/index.html.twig', [
            'logements'     => $logements,
            'currentSearch' => $search,
            'currentType'   => $type,
            'currentSort'   => $sort,
            'allTypes'      => $typesListe,
            'isConnected'   => true,   // force l'état connecté
            'userId'        => 4,      // id utilisateur statique
        ]);
    }
}