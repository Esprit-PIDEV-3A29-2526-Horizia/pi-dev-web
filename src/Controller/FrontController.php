<?php
// src/Controller/FrontController.php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Reservationlog;
use App\Entity\User;
use App\Repository\VoyageRepository;
use App\Service\GeminiService;
use App\Service\LogementSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontController extends AbstractController
{
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
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['redirect' => $this->generateUrl('app_login')], 401);
        }

        try {
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

            $allLogements = $em->getRepository(Logement::class)
                ->createQueryBuilder('l')
                ->where('l.disponibilite = :dispo')
                ->setParameter('dispo', true)
                ->getQuery()
                ->getResult();

            if (empty($allLogements)) {
                return $this->json(['message' => 'Aucun logement disponible.']);
            }

            $prompt = $this->buildPrompt($reservations, $allLogements);

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
                $recommendedLogements = array_slice($allLogements, 0, 6);
            }

            if (empty($recommendedLogements)) {
                $recommendedLogements = array_slice($allLogements, 0, 6);
            }

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