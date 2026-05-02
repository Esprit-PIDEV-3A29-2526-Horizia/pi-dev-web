<?php

namespace App\Service;

use App\Entity\Voyage;
use App\Repository\VoyageRepository;

class AiChatbotService
{
    public function __construct(
        private VoyageRepository $voyageRepository,
        private GeminiService $geminiService
    ) {
    }

    /**
     * @return array{reply: string, reply_html: string|null}
     */
    public function ask(string $message): array
    {
        $originalMessage = trim($message);
        $normalizedMessage = mb_strtolower($originalMessage);

        if ($normalizedMessage === '') {
            return $this->response('Veuillez écrire un message.');
        }

        if ($this->containsAny($normalizedMessage, [
            'bonjour', 'salut', 'hello', 'hi', 'salam', 'ahla'
        ])) {
            return $this->response(
                "Bonjour 👋 Je suis l'assistant Horozia. Je peux vous aider à trouver des voyages disponibles, recommander des destinations et proposer des voyages selon votre budget."
            );
        }

        if ($this->containsAny($normalizedMessage, [
            'tous les voyages',
            'affiche tous les voyages',
            'montre tous les voyages',
            'liste des voyages'
        ])) {
            $voyages = $this->voyageRepository->findAllVoyages(6);

            return $this->responseWithVoyages(
                $voyages,
                'Voici tous les voyages enregistrés :',
                'Aucun voyage trouvé.'
            );
        }

        if ($this->containsAny($normalizedMessage, [
            'voyages disponibles',
            'voyages dispo',
            'disponible'
        ])) {
            $voyages = $this->voyageRepository->findAvailableVoyages(6);

            return $this->responseWithVoyages(
                $voyages,
                'Voici les voyages disponibles :',
                'Aucun voyage disponible pour le moment.'
            );
        }

        if ($this->containsAny($normalizedMessage, [
            'petit budget',
            'pas cher',
            'moins cher',
            'budget'
        ])) {
            $voyages = $this->voyageRepository->findBudgetVoyagesBetween3000And4000(6);

            return $this->responseWithVoyages(
                $voyages,
                'Voici les voyages petit budget :',
                "Je n'ai trouvé aucun voyage à petit budget."
            );
        }

        if ($this->containsAny($normalizedMessage, [
            'voyages complets',
            'voyages terminés',
            'voyages sans places',
            'complet'
        ])) {
            $voyages = $this->voyageRepository->findCompletedVoyages(6);

            return $this->responseWithVoyages(
                $voyages,
                'Voici les voyages complets :',
                'Aucun voyage complet pour le moment.'
            );
        }

        if ($this->isPlacesToVisitRequest($normalizedMessage)) {
            return $this->askGeminiForPlacesOrItinerary($originalMessage);
        }

        if ($this->isRecommendationRequest($normalizedMessage)) {
            return $this->recommendVoyages($normalizedMessage);
        }

        $voyages = $this->voyageRepository->findMostReservedVoyages(3);

        return $this->responseWithVoyages(
            $voyages,
            'Voici quelques voyages populaires que je vous recommande :',
            "Je peux vous aider à afficher les voyages disponibles, les voyages petit budget, recommander un voyage ou proposer des lieux à visiter."
        );
    }

    /**
     * @return array{reply: string, reply_html: string|null}
     */
    private function askGeminiForPlacesOrItinerary(string $message): array
    {
        try {
            $days = $this->extractRequestedDays($message);

            $prompt = <<<PROMPT
Tu es l'assistant touristique intelligent de Horozia.
Réponds dans la même langue que l'utilisateur.

Demande utilisateur :
$message

Consignes :
- Si l'utilisateur demande des lieux à visiter, propose des monuments, musées, quartiers et activités.
- Si l'utilisateur demande un programme, fais un itinéraire clair jour par jour.
- Nombre de jours demandé : $days.
- Donne une réponse structurée, courte et utile.
- Donne des exemples connus quand c'est pertinent : Tour Eiffel, Arc de Triomphe, Louvre, Sagrada Familia, Colisée, Cappadoce, Médina de Tunis.
- Ne dis pas que tu es une IA.
PROMPT;

            $aiResponse = $this->geminiService->askForPlacesToVisit($prompt);

            if (!is_string($aiResponse) || trim($aiResponse) === '') {
                return $this->response("Je n'ai pas pu générer une réponse IA pour le moment.");
            }

            return $this->response($aiResponse);

        } catch (\Throwable $e) {
            return $this->response('Erreur Gemini : ' . $e->getMessage());
        }
    }

    /**
     * @return array{reply: string, reply_html: string|null}
     */
    private function recommendVoyages(string $message): array
    {
        $criteria = $this->extractCriteria($message);

        $voyages = $this->voyageRepository->searchSmart(
            $criteria['destination'],
            $criteria['budgetMin'],
            $criteria['themes'],
            $criteria['minPlaces'],
            $criteria['months'],
            6
        );

        if (empty($voyages)) {
            $voyages = $this->voyageRepository->findMostReservedVoyages(3);

            return $this->responseWithVoyages(
                $voyages,
                "Je n'ai pas trouvé exactement votre demande, mais voici les voyages les plus populaires :",
                "Je n'ai trouvé aucun voyage correspondant à votre demande."
            );
        }

        return [
            'reply' => $this->buildRecommendationIntro($criteria),
            'reply_html' => $this->formatVoyagesHtml($voyages),
        ];
    }

    private function isPlacesToVisitRequest(string $message): bool
    {
        return $this->containsAny($message, [
            'visiter',
            'que visiter',
            'que voir',
            'quoi voir',
            'que faire',
            'quoi faire',
            'activité',
            'activités',
            'lieu à visiter',
            'lieux à visiter',
            'site touristique',
            'sites touristiques',
            'programme',
            'itinéraire',
            'itineraire',
            'planning',
            'plan de voyage',
            'musée',
            'musee',
            'monument',
            'museum',
            'to visit',
            'things to do'
        ]);
    }

    private function isRecommendationRequest(string $message): bool
    {
        return $this->containsAny($message, [
            'recommande',
            'recommandation',
            'propose moi un voyage',
            'propose-moi un voyage',
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
            'luxe'
        ]);
    }

    /**
     * @return array{
     *     destination: string|null,
     *     budgetMin: float|null,
     *     themes: array<int, string>,
     *     minPlaces: int|null,
     *     months: array<int, int>
     * }
     */
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
            'italie', 'rome',
            'france', 'paris',
            'turquie', 'istanbul'
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

        foreach ($themeMap as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $themes[] = $theme;
                }
            }
        }

        $minPlaces = null;

        if (preg_match('/(\d+)\s*(personnes|personne|places|place)/iu', $message, $matches)) {
            $minPlaces = (int) $matches[1];
        }

        return [
            'destination' => $destination,
            'budgetMin' => $budgetMin,
            'themes' => array_values(array_unique($themes)),
            'minPlaces' => $minPlaces,
            'months' => $this->extractMonthsFromMessage($message),
        ];
    }

    private function extractBudget(string $message): ?float
    {
        if (preg_match('/(\d{3,5})\s*(dt|tnd|dinar|dinars|€|eur|euro|euros)?/iu', $message, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * @return array<int, int>
     */
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

    private function extractRequestedDays(string $message): int
    {
        if (preg_match('/(\d+)\s*(jours|jour|jrs|jr|days|day|d|أيام|ايام|نهارات)/iu', $message, $matches)) {
            $days = (int) $matches[1];

            return max(1, min($days, 10));
        }

        return 3;
    }

    /**
     * @param array{
     *     destination: string|null,
     *     budgetMin: float|null,
     *     themes: array<int, string>,
     *     minPlaces: int|null,
     *     months: array<int, int>
     * } $criteria
     */
    private function buildRecommendationIntro(array $criteria): string
    {
        $parts = ['Voici mes recommandations les plus adaptées'];

        if (!empty($criteria['destination'])) {
            $parts[] = 'pour ' . $criteria['destination'];
        }

        if (!empty($criteria['budgetMin'])) {
            $parts[] = 'avec votre budget';
        }

        if (!empty($criteria['themes'])) {
            $parts[] = 'selon vos préférences';
        }

        if (!empty($criteria['months'])) {
            $parts[] = 'pour votre période souhaitée';
        }

        return implode(' ', $parts) . ' :';
    }

    /**
     * @param array<int, Voyage> $voyages
     * @return array{reply: string, reply_html: string|null}
     */
    private function responseWithVoyages(array $voyages, string $successMessage, string $emptyMessage): array
    {
        if (empty($voyages)) {
            return $this->response($emptyMessage);
        }

        return [
            'reply' => $successMessage,
            'reply_html' => $this->formatVoyagesHtml($voyages),
        ];
    }

    /**
     * @return array{reply: string, reply_html: string|null}
     */
    private function response(string $reply, ?string $replyHtml = null): array
    {
        return [
            'reply' => $reply,
            'reply_html' => $replyHtml,
        ];
    }

    /**
     * @param array<int, string> $keywords
     */
    private function containsAny(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, Voyage> $voyages
     */
    private function formatVoyagesHtml(array $voyages): string
    {
        $html = '<div class="chatbot-response-block">';

        foreach ($voyages as $voyage) {
            $titre = (string) ($voyage->getTitre() ?? '');
            $destination = (string) ($voyage->getDestination() ?? '');
            $image = (string) ($voyage->getImageUrl() ?? '');
            $prix = (float) ($voyage->getPrix() ?? 0);
            $placesRestantes = (int) ($voyage->getPlacesRestantes() ?? 0);

            if ($image === '') {
                $image = '/front/pacific/images/destination-1.jpg';
            }

            if (!str_starts_with($image, 'http')) {
                $image = '/' . ltrim($image, '/');
            }

            $dateDepart = $voyage->getDateDepart()?->format('d/m/Y') ?? 'Non définie';
            $dateRetour = $voyage->getDateRetour()?->format('d/m/Y') ?? 'Non définie';

            $detailUrl = '/voyage/' . $voyage->getId() . '?currency=TND';

            $html .= '
                <div class="chatbot-voyage-card">
                    <div class="chatbot-voyage-image-wrapper">
                        <img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '" class="chatbot-voyage-image">
                    </div>
                    <div class="chatbot-voyage-content">
                        <div class="chatbot-voyage-title">' . htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') . '</div>
                        <div class="chatbot-voyage-line"><strong>Destination :</strong> ' . htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') . '</div>
                        <div class="chatbot-voyage-line"><strong>Prix :</strong> ' . number_format($prix, 0, ',', ' ') . ' DT</div>
                        <div class="chatbot-voyage-line"><strong>Dates :</strong> ' . htmlspecialchars($dateDepart, ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($dateRetour, ENT_QUOTES, 'UTF-8') . '</div>
                        <div class="chatbot-voyage-line"><strong>Places restantes :</strong> ' . $placesRestantes . '</div>
                        <a href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '" class="chatbot-link">Voir détails</a>
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return $html;
    }
}