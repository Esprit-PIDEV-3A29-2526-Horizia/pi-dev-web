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
        $originalMessage = trim($message);
        $normalizedMessage = mb_strtolower($originalMessage);

        if ($normalizedMessage === '') {
            return [
                'reply' => 'Veuillez écrire un message.',
                'reply_html' => null,
            ];
        }

        if (
            str_contains($normalizedMessage, 'tous les voyages') ||
            str_contains($normalizedMessage, 'affiche tous les voyages') ||
            str_contains($normalizedMessage, 'montre tous les voyages')
        ) {
            $voyages = $this->voyageRepository->findAllVoyages();

            return [
                'reply' => empty($voyages) ? 'Aucun voyage trouvé.' : 'Voici tous les voyages enregistrés :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        if (
            str_contains($normalizedMessage, 'voyages dispo') ||
            str_contains($normalizedMessage, 'voyages disponibles')
        ) {
            $voyages = $this->voyageRepository->findAvailableVoyages();

            return [
                'reply' => empty($voyages) ? 'Aucun voyage disponible pour le moment.' : 'Voici tous les voyages disponibles :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        if (
            str_contains($normalizedMessage, 'voyages complets') ||
            str_contains($normalizedMessage, 'voyages terminés') ||
            str_contains($normalizedMessage, 'voyages sans places')
        ) {
            $voyages = $this->voyageRepository->findCompletedVoyages();

            return [
                'reply' => empty($voyages) ? 'Aucun voyage complet pour le moment.' : 'Voici les voyages complets :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        if (
            str_contains($normalizedMessage, 'petit budget') ||
            str_contains($normalizedMessage, 'pas cher')
        ) {
            $voyages = $this->voyageRepository->findBudgetVoyagesBetween3000And4000();

            return [
                'reply' => empty($voyages)
                    ? "Je n'ai trouvé aucun voyage a petit budget ."
                    : "Voici les voyages petit budget :",
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

        if ($this->isPlacesToVisitRequest($normalizedMessage)) {
            return [
                'reply' => $this->openAiService->askForPlacesToVisit($originalMessage),
                'reply_html' => null,
            ];
        }

        if (
            str_contains($normalizedMessage, 'recommande') ||
            str_contains($normalizedMessage, 'recommandation') ||
            str_contains($normalizedMessage, 'propose moi un voyage')
        ) {
            $criteria = $this->extractCriteria($normalizedMessage);

            if (
                $criteria['destination'] !== null ||
                $criteria['budgetMin'] !== null ||
                !empty($criteria['themes']) ||
                $criteria['minPlaces'] !== null ||
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
                        'reply' => "Je n'ai trouvé aucun voyage correspondant à votre demande. Essayez une autre saison, un autre budget ou une autre destination.",
                        'reply_html' => null,
                    ];
                }

                return [
                    'reply' => $this->buildRecommendationIntro($criteria),
                    'reply_html' => $this->formatVoyagesHtml($voyages),
                ];
            }

            $voyages = $this->voyageRepository->findMostReservedVoyages(3);

            return [
                'reply' => empty($voyages)
                    ? 'Aucun voyage populaire disponible pour le moment.'
                    : 'Voici les voyages les plus recommandés selon leur nombre de réservations :',
                'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
            ];
        }

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
                    'reply' => "Je n'ai trouvé aucun voyage correspondant à votre demande. Essayez une autre saison, un autre budget ou une autre destination.",
                    'reply_html' => null,
                ];
            }

            return [
                'reply' => $this->buildRecommendationIntro($criteria),
                'reply_html' => $this->formatVoyagesHtml($voyages),
            ];
        }

        $voyages = $this->voyageRepository->findMostReservedVoyages(3);

        return [
            'reply' => empty($voyages)
                ? "Je peux vous aider à afficher tous les voyages, les voyages disponibles, les voyages complets, les voyages petit budget ou suggérer des lieux à visiter dans un pays."
                : 'Voici quelques voyages populaires que je vous recommande :',
            'reply_html' => empty($voyages) ? null : $this->formatVoyagesHtml($voyages),
        ];
    }

    private function isPlacesToVisitRequest(string $message): bool
    {
        $keywords = [
            'visiter',
            'place à voir',
            'places à voir',
            'endroit à voir',
            'endroits à voir',
            'que voir',
            'quoi voir',
            'que faire',
            'quoi faire',
            'truc à faire',
            'trucs à faire',
            'activité',
            'activités',
            'lieu à visiter',
            'lieux à visiter',
            'site touristique',
            'sites touristiques',
            'à faire',
            'a faire',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isRecommendationRequest(string $message): bool
    {
        $keywords = [
            'je veux un voyage',
            'je cherche un voyage',
            'je veux partir',
            'partir en',
            'voyage en',
            'pour 2 personnes',
            'pour 3 personnes',
            'pour 4 personnes',
            'été',
            'ete',
            'hiver',
            'printemps',
            'automne',
            'plage',
            'nature',
            'romantique',
            'aventure',
            'ville',
            'luxe',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function extractCriteria(string $message): array
    {
        $budgetMin = $this->extractBudget($message);

        $destination = null;
        $knownDestinations = [
            'japon', 'tokyo',
            'australie', 'sydney',
            'bali',
            'suisse', 'zurich',
            'espagne', 'barcelone',
            'athènes', 'athenes', 'grèce', 'grece',
            'tozeur', 'tunisie',
            'italie', 'france', 'turquie'
        ];

        foreach ($knownDestinations as $place) {
            if (str_contains($message, $place)) {
                $destination = $place;
                break;
            }
        }

        $themes = [];
        $themeMap = [
            'plage' => ['plage', 'mer', 'soleil', 'détente', 'detente'],
            'nature' => ['nature', 'montagne', 'randonnée', 'randonnee', 'calme'],
            'ville' => ['ville', 'urbain', 'shopping', 'culture'],
            'romantique' => ['romantique', 'couple', 'lune de miel'],
            'aventure' => ['aventure', 'exploration', 'activité', 'activite'],
            'luxe' => ['luxe', 'premium', 'haut de gamme'],
        ];

        foreach ($themeMap as $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $themes[] = $keyword;
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
            'budgetMin' => $budgetMin,
            'themes' => array_values(array_unique($themes)),
            'minPlaces' => $minPlaces,
            'months' => $months,
        ];
    }

    private function extractBudget(string $message): ?float
    {
        if (preg_match('/(\d{3,5})\s*(dt|dinar|dinars)?/i', $message, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function extractMonthsFromMessage(string $message): array
    {
        $months = [];

        $monthMap = [
            'janvier' => 1,
            'février' => 2,
            'fevrier' => 2,
            'mars' => 3,
            'avril' => 4,
            'mai' => 5,
            'juin' => 6,
            'juillet' => 7,
            'août' => 8,
            'aout' => 8,
            'septembre' => 9,
            'octobre' => 10,
            'novembre' => 11,
            'décembre' => 12,
            'decembre' => 12,
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
        $parts = ['Voici mes recommandations les plus adaptées'];

        if (!empty($criteria['months'])) {
            $parts[] = 'pour votre période souhaitée';
        }

        if (!empty($criteria['budgetMin'])) {
            $parts[] = 'dans votre budget';
        }

        if (!empty($criteria['destination'])) {
            $parts[] = 'pour cette destination';
        }

        return implode(' ', $parts) . ' :';
    }

    private function formatVoyagesHtml(array $voyages): string
    {
        $html = '<div class="chatbot-response-block">';

        foreach ($voyages as $voyage) {
            $image = $voyage->getImageUrl() ?: '/front/pacific/images/destination-1.jpg';

            if (!str_starts_with($image, 'http')) {
                $image = '/' . ltrim($image, '/');
            }

            $detailUrl = '/voyage/' . $voyage->getId() . '?currency=TND';

            $html .= '
                <div class="chatbot-voyage-card">
                    <div class="chatbot-voyage-image-wrapper">
                        <img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($voyage->getTitre()) . '" class="chatbot-voyage-image">
                    </div>
                    <div class="chatbot-voyage-content">
                        <div class="chatbot-voyage-title">' . htmlspecialchars($voyage->getTitre()) . '</div>
                        <div class="chatbot-voyage-line"><strong>Destination :</strong> ' . htmlspecialchars($voyage->getDestination()) . '</div>
                        <div class="chatbot-voyage-line"><strong>Prix :</strong> ' . number_format((float) $voyage->getPrix(), 0, ',', ' ') . ' DT</div>
                        <div class="chatbot-voyage-line"><strong>Dates :</strong> ' . $voyage->getDateDepart()?->format('d/m/Y') . ' - ' . $voyage->getDateRetour()?->format('d/m/Y') . '</div>
                        <div class="chatbot-voyage-line"><strong>Places restantes :</strong> ' . (int) $voyage->getPlacesRestantes() . '</div>
                        <a href="' . htmlspecialchars($detailUrl) . '" class="chatbot-link">Voir détails</a>
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return $html;
    }
}