<?php
// src/Controller/FrontController.php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservationlog;
use App\Entity\User;
use App\Entity\Events;
use App\Repository\VoyageRepository;
use App\Service\GeminiService;
use App\Service\LogementSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{

    #[Route('/events', name: 'app_front_events', methods: ['GET'])]
    public function publicEvents(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search');
        $priceLimit = $request->query->get('price_limit');
        $page = $request->query->getInt('page', 1);
        $limit = 6; // Items per page (3 per row * 2 rows = 6)

        $qb = $entityManager->getRepository(Events::class)
            ->createQueryBuilder('e')
            ->where('e.statut != :termine')
            ->setParameter('termine', 'termine')
            ->orderBy('e.date_debut', 'ASC');

        if ($search) {
            $qb->andWhere('e.titre LIKE :search OR e.location LIKE :search OR e.categorie LIKE :search')
            ->setParameter('search', '%' . $search . '%');
        }

        if ($priceLimit && is_numeric($priceLimit)) {
            $qb->andWhere('e.prix <= :priceLimit')
            ->setParameter('priceLimit', $priceLimit);
        }

        // Get total count for pagination
        $totalEvents = $qb->select('COUNT(e.id_event)')
                        ->getQuery()
                        ->getSingleScalarResult();

        $totalPages = ceil($totalEvents / $limit);
        
        // Ensure page is valid
        if ($page < 1) $page = 1;
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        
        $offset = ($page - 1) * $limit;
        
        // Get paginated results
        $events = $qb->select('e')
                    ->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        return $this->render('front/event/events.html.twig', [
            'events' => $events,
            'total_events' => $totalEvents,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit,
            'search' => $search,
            'price_limit' => $priceLimit,
        ]);
    }

    #[Route('/', name: 'app_front_home')]
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

        $user = $this->getUser();
        $userId = $user instanceof User ? $user->getId() : null;

        return $this->render('front/logement/index.html.twig', [
            'logements'     => $logements,
            'currentSearch' => $search,
            'currentType'   => $type,
            'currentSort'   => $sort,
            'allTypes'      => $typesListe,
            'isConnected'   => $user !== null,
            'userId'        => $userId,
        ]);
    }

    #[Route('/logements/recommendations', name: 'app_front_logement_recommendations', methods: ['GET'])]
public function recommendations(GeminiService $geminiService, EntityManagerInterface $em): JsonResponse
{
    try {
                $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'error' => 'Utilisateur non authentifié'], 401);
        }

        // Historique des réservations
        $reservations = $em->getRepository(Reservationlog::class)
            ->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['confirmée', 'terminée'])
            ->getQuery()
            ->getResult();

        if (empty($reservations)) {
            return $this->json(['message' => 'Aucune réservation antérieure.']);
        }

        // Tous les logements disponibles
        $allLogements = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->where('l.disponibilite = :dispo')
            ->setParameter('dispo', true)
            ->getQuery()
            ->getResult();

        if (empty($allLogements)) {
            return $this->json(['message' => 'Aucun logement disponible.']);
        }

        // Prompt pour l'IA
        $prompt = $this->buildPrompt($reservations, $allLogements);

        // Appel à Gemini avec fallback
        try {
            $responseText = $geminiService->generateRecommendations($prompt);
            $jsonString = preg_replace('/```json\s*|\s*```/', '', $responseText);
            $recommendations = json_decode($jsonString, true);
            $recommendedIds = $recommendations['recommended_ids'] ?? [];

            if (!empty($recommendedIds)) {
                $recommendedLogements = $em->getRepository(Logement::class)
                    ->createQueryBuilder('l')
                    ->where('l.id IN (:ids)')
                    ->setParameter('ids', $recommendedIds)
                    ->getQuery()
                    ->getResult();
            } else {
                $recommendedLogements = [];
            }
        } catch (\Exception $e) {
            // Si l'IA échoue, on prend les 6 premiers logements
            $recommendedLogements = array_slice($allLogements, 0, 6);
        }

        // Fallback final : si aucun logement n'est trouvé, on prend les 6 premiers
        if (empty($recommendedLogements)) {
            $recommendedLogements = array_slice($allLogements, 0, 6);
        }

        // Génération HTML des cartes
        $html = '';
        foreach ($recommendedLogements as $logement) {
            $imageUrl = $logement->getImage() ?: '/front/pacific/images/destination-1.jpg';
            $nom = htmlspecialchars($logement->getNom() ?? '');
            $type = htmlspecialchars($logement->getType() ?? '');
            $adresse = htmlspecialchars($logement->getAdresse() ?? '');
            $adresseCourte = htmlspecialchars(substr($adresse, 0, 40));
            $capacite = $logement->getCapacite() ?? 0;
            $tarif = number_format($logement->getTarifNuit() ?? 0, 0, ',', ' ');
            $equipement = $logement->getEquipement();
            $equipementHtml = '';
            if ($equipement) {
                $equipements = explode(',', $equipement);
                $equipementHtml = '<div>';
                $i = 0;
                foreach ($equipements as $equip) {
                    if ($i < 4) {
                        $equipementHtml .= '<span class="equipement-badge">' . htmlspecialchars(trim($equip)) . '</span>';
                    } else {
                        break;
                    }
                    $i++;
                }
                if (count($equipements) > 4) {
                    $equipementHtml .= '<span class="equipement-badge">+' . (count($equipements) - 4) . '</span>';
                }
                $equipementHtml .= '</div>';
            }

            $html .= '<div class="col-md-4 ftco-animate mb-4">
                <div class="flip-card">
                    <div class="flip-card-inner">
                        <div class="flip-card-front">
                            <div class="flip-card-front-img" style="background-image: url(\'' . $imageUrl . '\');">
                                <div class="price-badge">' . $tarif . ' DT / nuit</div>
                            </div>
                            <div class="flip-card-front-content">
                                <div class="flip-card-front-title">' . $nom . '</div>
                                <div class="flip-card-front-type">' . $type . '</div>
                                <div class="flip-card-front-location">
                                    <i class="fa fa-map-marker"></i> ' . $adresseCourte . '
                                </div>
                            </div>
                        </div>
                        <div class="flip-card-back">
                            <div>
                                <h3>' . $nom . '</h3>
                                <p><i class="fa fa-users"></i> Capacité : ' . $capacite . ' personnes</p>
                                <p><i class="fa fa-tag"></i> Type : ' . $type . '</p>
                                <p><i class="fa fa-map-marker"></i> ' . $adresse . '</p>
                                <p><i class="fa fa-money"></i> ' . $tarif . ' DT / nuit</p>
                                ' . $equipementHtml . '
                            </div>
                            <button type="button" class="btn-reserver" data-id="' . $logement->getId() . '">
                                <i class="fa fa-calendar-check-o"></i> Réserver
                            </button>
                        </div>
                    </div>
                </div>
            </div>';
        }

        return $this->json([
            'status' => 'completed',
            'html'   => $html,
            'count'  => count($recommendedLogements)
        ]);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 500);
    }
}
    private function buildPrompt(array $reservations, array $candidates): string
    {
        $resumeReservations = '';
        foreach ($reservations as $res) {
            $log = $res->getLogement();
            $resumeReservations .= sprintf(
                "- %s (Type: %s, Capacité: %d, Prix: %.2f DT, Équipements: %s)\n",
                $log->getNom(),
                $log->getType(),
                $log->getCapacite(),
                $log->getTarifNuit(),
                $log->getEquipement() ?? 'Aucun'
            );
        }

        $resumeCandidates = '';
        foreach ($candidates as $log) {
            $resumeCandidates .= sprintf(
                "- ID: %d | %s (Type: %s, Capacité: %d, Prix: %.2f DT, Équipements: %s)\n",
                $log->getId(),
                $log->getNom(),
                $log->getType(),
                $log->getCapacite(),
                $log->getTarifNuit(),
                $log->getEquipement() ?? 'Aucun'
            );
        }

        return sprintf(
            "Tu es un assistant expert en recommandation de logements de vacances.
Analyse l'historique des réservations de l'utilisateur et sélectionne les logements les plus pertinents parmi ceux proposés.

## Historique des réservations de l'utilisateur
%s

## Logements disponibles (parmi lesquels choisir)
%s

Règles:
- Retourne uniquement du JSON valide
- Structure: {\"recommended_ids\": [id1, id2, ...], \"reason\": \"brève justification\"}
- Sélectionne entre 3 et 6 logements
- Base-toi sur le type, la capacité, le prix, les équipements et la diversité

JSON:",
            $resumeReservations,
            $resumeCandidates
        );
    }
}