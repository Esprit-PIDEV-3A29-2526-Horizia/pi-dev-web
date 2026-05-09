<?php

namespace App\Service;

use App\Repository\VoyageRepository;

class AiChatbotService
{
    public function __construct(
        private VoyageRepository $voyageRepository,
        private OpenAiService $openAiService
    ) {
    }

    public function ask(string $message): array
    {
        $originalMessage   = trim($message);
        $normalizedMessage = mb_strtolower($originalMessage);

        if ($normalizedMessage === '') {
            return [
                'reply'      => 'Veuillez écrire un message.',
                'reply_html' => null,
            ];
        }

        // ── PRIORITÉ 1 : Lieux à visiter / programmes / itinéraires → OpenAI ──
        if ($this->isPlacesToVisitRequest($normalizedMessage) || $this->isProgramRequest($normalizedMessage)) {
            return [
                'reply'      => $this->openAiService->askForPlacesToVisit($originalMessage),
                'reply_html' => null,
            ];
        }

        // ── PRIORITÉ 2 : Tous les voyages ─────────────────────────────────────
        if (
            str_contains($normalizedMessage, 'tous les voyages') ||
            str_contains($normalizedMessage, 'affiche tous les voyages') ||
            str_contains($normalizedMessage, 'montre tous les voyages') ||
            str_contains($normalizedMessage, 'liste des voyages') ||
            str_contains($normalizedMessage, 'voir tous les voyages')
        ) {
            $voyages = $this->voyageRepository->findAllVoyages();

            return [
                'reply'      => empty($voyages) ? 'Aucun voyage trouvé.' : 'Voici tous les voyages enregistrés :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        // ── PRIORITÉ 3 : Voyages disponibles ──────────────────────────────────
        if (
            str_contains($normalizedMessage, 'voyages dispo') ||
            str_contains($normalizedMessage, 'voyages disponibles') ||
            str_contains($normalizedMessage, 'voyages avec des places') ||
            str_contains($normalizedMessage, 'voyages ouverts')
        ) {
            $voyages = $this->voyageRepository->findAvailableVoyages();

            return [
                'reply'      => empty($voyages) ? 'Aucun voyage disponible pour le moment.' : 'Voici tous les voyages disponibles :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        // ── PRIORITÉ 4 : Voyages complets ─────────────────────────────────────
        if (
            str_contains($normalizedMessage, 'voyages complets') ||
            str_contains($normalizedMessage, 'voyages terminés') ||
            str_contains($normalizedMessage, 'voyages termines') ||
            str_contains($normalizedMessage, 'voyages sans places') ||
            str_contains($normalizedMessage, 'voyages pleins')
        ) {
            $voyages = $this->voyageRepository->findCompletedVoyages();

            return [
                'reply'      => empty($voyages) ? 'Aucun voyage complet pour le moment.' : 'Voici les voyages complets :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        // ── PRIORITÉ 5 : Petit budget ─────────────────────────────────────────
        if (
            str_contains($normalizedMessage, 'petit budget') ||
            str_contains($normalizedMessage, 'pas cher') ||
            str_contains($normalizedMessage, 'moins cher') ||
            str_contains($normalizedMessage, 'économique') ||
            str_contains($normalizedMessage, 'economique') ||
            str_contains($normalizedMessage, 'abordable')
        ) {
            $voyages = $this->voyageRepository->findBudgetVoyagesBetween3000And4000();

            return [
                'reply'      => empty($voyages) ? "Je n'ai trouvé aucun voyage à petit budget." : 'Voici les voyages petit budget :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        // ── PRIORITÉ 6 : Recommandation avec mot-clé explicite ────────────────
        if (
            str_contains($normalizedMessage, 'recommande') ||
            str_contains($normalizedMessage, 'recommandation') ||
            str_contains($normalizedMessage, 'propose moi un voyage') ||
            str_contains($normalizedMessage, 'propose-moi un voyage') ||
            str_contains($normalizedMessage, 'conseille')
        ) {
            $criteria = $this->extractCriteria($normalizedMessage);

            if (
                $criteria['destination'] !== null ||
                $criteria['budgetMin']   !== null ||
                !empty($criteria['themes']) ||
                $criteria['minPlaces']   !== null ||
                !empty($criteria['months'])
            ) {
                $voyages = $this->voyageRepository->searchSmart(
                    $criteria['destination'],
                    $criteria['budgetMin'],
                    $criteria['themes'],
                    $criteria['minPlaces'],
                    $criteria['months'],
                    6
                );

                if (empty($voyages)) {
                    return [
                        'reply'      => "Je n'ai trouvé aucun voyage correspondant à votre demande. Essayez une autre saison, un autre budget ou une autre destination.",
                        'reply_html' => null,
                    ];
                }

                return [
                    'reply'      => $this->buildRecommendationIntro($criteria),
                    'reply_html' => $this->formatVoyagesHtml($voyages),
                ];
            }

            $voyages = $this->voyageRepository->findMostReservedVoyages(3);

            return [
                'reply'      => empty($voyages) ? 'Aucun voyage populaire disponible pour le moment.' : 'Voici les voyages les plus recommandés selon leur nombre de réservations :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        // ── PRIORITÉ 7 : Intention de voyage (saison, thème, destination…) ────
        if ($this->isRecommendationRequest($normalizedMessage)) {
            $criteria = $this->extractCriteria($normalizedMessage);

            $voyages = $this->voyageRepository->searchSmart(
                $criteria['destination'],
                $criteria['budgetMin'],
                $criteria['themes'],
                $criteria['minPlaces'],
                $criteria['months'],
                6
            );

            if (empty($voyages)) {
                return [
                    'reply'      => "Je n'ai trouvé aucun voyage correspondant à votre demande. Essayez une autre saison, un autre budget ou une autre destination.",
                    'reply_html' => null,
                ];
            }

            return [
                'reply'      => $this->buildRecommendationIntro($criteria),
                'reply_html' => $this->formatVoyagesHtml($voyages),
            ];
        }

        // ── FALLBACK : voyages populaires ─────────────────────────────────────
        $voyages = $this->voyageRepository->findMostReservedVoyages(3);

        return [
            'reply'      => empty($voyages)
                ? "Je peux vous aider à afficher tous les voyages, les voyages disponibles, les voyages à petit budget, ou vous suggérer des lieux à visiter dans un pays."
                : 'Voici quelques voyages populaires que je vous recommande :',
            'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Détection : lieux à visiter / activités → OpenAI
    // ─────────────────────────────────────────────────────────────────────────

    private function isPlacesToVisitRequest(string $message): bool
    {
        $keywords = [
            'visiter',
            'à visiter', 'a visiter',
            'place à voir', 'places à voir',
            'endroit à voir', 'endroits à voir',
            'que voir', 'quoi voir',
            'lieu à visiter', 'lieux à visiter',
            'site touristique', 'sites touristiques',
            'monument', 'monuments',
            'attraction', 'attractions',
            'musée', 'musee',
            'quartier à découvrir',
            'incontournable', 'incontournables',
            'que faire', 'quoi faire',
            'truc à faire', 'trucs à faire',
            'activité', 'activités',
            'à faire', 'a faire',
            'expérience', 'expériences',
            'sortie', 'sorties',
            'loisir', 'loisirs',
            'spécialité', 'spécialités',
            'cuisine locale',
            'gastronomie',
            'culture locale',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Détection : programme / itinéraire → OpenAI
    // ─────────────────────────────────────────────────────────────────────────

    private function isProgramRequest(string $message): bool
    {
        $keywords = [
            'programme',
            'itinéraire', 'itineraire',
            'planning',
            'agenda',
            'plan de voyage',
            'circuit',
            'jour par jour',
            'journée type',
            'que visiter',
            'comment organiser',
            'organiser mon voyage',
            'organiser un voyage',
            'séjour de', 'sejour de',
            '1 semaine', '2 semaines', '3 semaines',
            'une semaine', 'deux semaines',
            '1 jour', '2 jours', '3 jours', '4 jours', '5 jours', '6 jours', '7 jours',
            'une journée', 'un week-end',
            'week-end', 'weekend', 'long week-end',
            'que faire en', 'quoi faire en',
            'que voir en', 'quoi voir en',
            'que faire à', 'quoi faire à',
            'que voir à', 'quoi voir à',
            'bon plan', 'bons plans',
            'meilleur endroit', 'meilleurs endroits',
            'conseil pour', 'conseils pour',
            'guide pour', 'guide de voyage',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Détection : intention de voyage → recherche BDD
    // ─────────────────────────────────────────────────────────────────────────

    private function isRecommendationRequest(string $message): bool
    {
        $keywords = [
            'je veux un voyage',
            'je cherche un voyage',
            'je veux partir',
            'je souhaite partir',
            'envie de voyager',
            'partir en',
            'voyage en',
            'pour 2 personnes',
            'pour 3 personnes',
            'pour 4 personnes',
            'pour 5 personnes',
            'été', 'ete',
            'hiver',
            'printemps',
            'automne',
            'plage',
            'nature',
            'romantique',
            'aventure',
            'luxe',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Extraction des critères de recherche
    // ─────────────────────────────────────────────────────────────────────────

    private function extractCriteria(string $message): array
    {
        $budgetMin = $this->extractBudget($message);

        $destination = null;
        $knownDestinations = [
            'japon', 'tokyo',
            'australie', 'sydney',
            'bali',
            'suisse',
            'espagne', 'barcelone',
            'athènes', 'athenes', 'grèce', 'grece',
            'tozeur', 'tunisie',
            'italie', 'rome',
            'france', 'paris',
            'turquie', 'istanbul',
            'angleterre', 'londres',
            'maldives',
            'new york',
            'seychelles',
            'sri lanka',
            'dubai',
            'vienne', 'autriche',
        ];

        foreach ($knownDestinations as $place) {
            if (str_contains($message, $place)) {
                $destination = $place;
                break;
            }
        }

        // Thèmes matchés sur titre / destination / description (via searchSmart)
        $themes = [];
        $themeMap = [
            'plage'       => ['plage', 'mer', 'soleil', 'détente', 'detente', 'plages'],
            'nature'      => ['nature', 'montagne', 'randonnée', 'randonnee', 'calme', 'écotourisme', 'ecotourisme'],
            'urbain'      => ['urbain', 'ville', 'shopping', 'culture', 'métropole', 'metropole'],
            'romantique'  => ['romantique', 'couple', 'lune de miel', 'honeymoon'],
            'aventure'    => ['aventure', 'exploration', 'safari', 'sport'],
            'luxe'        => ['luxe', 'premium', 'haut de gamme', 'prestige'],
            'gastronomie' => ['gastronomie', 'culinaire', 'cuisine'],
            'culturel'    => ['culturel', 'historique', 'patrimoine'],
        ];

        foreach ($themeMap as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $themes[] = $theme;
                    break;
                }
            }
        }

        $minPlaces = null;
        if (preg_match('/(\d+)\s*(personnes|personne|places|place)/i', $message, $matches)) {
            $minPlaces = (int) $matches[1];
        }

        $months = $this->extractMonthsFromMessage($message);

        return [
            'destination' => $destination,
            'budgetMin'   => $budgetMin,
            'themes'      => array_values(array_unique($themes)),
            'minPlaces'   => $minPlaces,
            'months'      => $months,
        ];
    }

    private function extractBudget(string $message): ?float
    {
        if (preg_match('/(\d{3,6})\s*(dt|dinar|dinars|€|eur)?/i', $message, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function extractMonthsFromMessage(string $message): array
    {
        $months = [];

        $monthMap = [
            'janvier'   => 1,
            'février'   => 2,
            'fevrier'   => 2,
            'mars'      => 3,
            'avril'     => 4,
            'mai'       => 5,
            'juin'      => 6,
            'juillet'   => 7,
            'août'      => 8,
            'aout'      => 8,
            'septembre' => 9,
            'octobre'   => 10,
            'novembre'  => 11,
            'décembre'  => 12,
            'decembre'  => 12,
        ];

        foreach ($monthMap as $name => $number) {
            if (str_contains($message, $name)) {
                $months[] = $number;
            }
        }

        if (str_contains($message, 'été') || str_contains($message, 'ete')) {
            $months = array_merge($months, [6, 7, 8]);
        }
        if (str_contains($message, 'printemps')) {
            $months = array_merge($months, [3, 4, 5]);
        }
        if (str_contains($message, 'hiver')) {
            $months = array_merge($months, [12, 1, 2]);
        }
        if (str_contains($message, 'automne')) {
            $months = array_merge($months, [9, 10, 11]);
        }

        return array_values(array_unique($months));
    }

    private function buildRecommendationIntro(array $criteria): string
    {
        $parts = ['Voici mes recommandations'];

        if (!empty($criteria['destination'])) {
            $parts[] = 'pour ' . ucfirst($criteria['destination']);
        }
        if (!empty($criteria['months'])) {
            $parts[] = 'pour votre période souhaitée';
        }
        if (!empty($criteria['budgetMin'])) {
            $parts[] = 'dans votre budget';
        }
        if (!empty($criteria['themes'])) {
            $parts[] = 'selon vos centres d\'intérêt';
        }

        return implode(' ', $parts) . ' :';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Formatage HTML des cartes voyage
    // ─────────────────────────────────────────────────────────────────────────

    private function formatVoyagesHtml(array $voyages): string
    {
        $html = '<div class="chatbot-response-block">';

        foreach ($voyages as $voyage) {
            $image = $voyage->getImageUrl() ?: '/front/pacific/images/destination-1.jpg';

            if (!str_starts_with($image, 'http')) {
                $image = '/' . ltrim($image, '/');
            }

            $detailUrl       = '/voyage/' . $voyage->getId() . '?currency=TND';
            $placesRestantes = (int) $voyage->getPlacesRestantes();

            if ($placesRestantes === 0) {
                $placesLabel = '⛔ Complet';
                $placesClass = 'chatbot-places-full';
            } elseif ($placesRestantes <= 3) {
                $placesLabel = "⚠️ {$placesRestantes} place(s) restante(s)";
                $placesClass = 'chatbot-places-low';
            } else {
                $placesLabel = "✅ {$placesRestantes} places disponibles";
                $placesClass = 'chatbot-places-ok';
            }

            $html .= '
                <div class="chatbot-voyage-card">
                    <div class="chatbot-voyage-image-wrapper">
                        <img src="' . htmlspecialchars($image) . '"
                             alt="' . htmlspecialchars((string) $voyage->getTitre()) . '"
                             class="chatbot-voyage-image">
                    </div>
                    <div class="chatbot-voyage-content">
                        <div class="chatbot-voyage-title">' . htmlspecialchars((string) $voyage->getTitre()) . '</div>
                        <div class="chatbot-voyage-line">
                            <strong>Destination :</strong> ' . htmlspecialchars((string) $voyage->getDestination()) . '
                        </div>
                        <div class="chatbot-voyage-line">
                            <strong>Prix :</strong> ' . number_format((float) $voyage->getPrix(), 0, ',', ' ') . ' DT
                        </div>
                        <div class="chatbot-voyage-line">
                            <strong>Dates :</strong>
                            ' . ($voyage->getDateDepart()?->format('d/m/Y') ?? 'N/A') . '
                            →
                            ' . ($voyage->getDateRetour()?->format('d/m/Y') ?? 'N/A') . '
                        </div>
                        <div class="chatbot-voyage-line ' . $placesClass . '">
                            ' . $placesLabel . '
                        </div>
                        <a href="' . htmlspecialchars($detailUrl) . '" class="chatbot-link">Voir détails</a>
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return $html;
    }
}