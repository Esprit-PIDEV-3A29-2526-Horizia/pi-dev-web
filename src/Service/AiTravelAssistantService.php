<?php

namespace App\Service;

class AiTravelAssistantService
{
    public function __construct(
        private GeminiService $geminiService,
        private OpenAiService $openAiService
    ) {
    }

    /**
     * @param array{
     *     destination?: string|null,
     *     budget?: float|null,
     *     days?: int|null
     * } $analysis
     * @param list<array{
     *     titre: string,
     *     destination: string,
     *     prix: float|int|string
     * }> $voyagesContext
     */
    public function generateProgram(string $message, array $analysis, array $voyagesContext = []): string
    {
        $prompt = $this->buildPrompt($message, $analysis, $voyagesContext);

        $geminiResponse = $this->normalizeAiResponse(
            $this->geminiService->askTravelAssistant($prompt)
        );

        if ($this->isValidAiResponse($geminiResponse)) {
            return $geminiResponse;
        }

        $openAiResponse = $this->normalizeAiResponse(
            $this->openAiService->askTravelAssistant($prompt)
        );

        if ($this->isValidAiResponse($openAiResponse)) {
            return $openAiResponse;
        }

        return $this->localSmartFallback($analysis);
    }

    /**
     * @param string|array<string, bool|int|string|null>|null $response
     */
    private function normalizeAiResponse(string|array|null $response): ?string
    {
        if (is_string($response)) {
            return $response;
        }

        if (is_array($response)) {
            if (isset($response['message']) && is_string($response['message'])) {
                return $response['message'];
            }

            if (isset($response['content']) && is_string($response['content'])) {
                return $response['content'];
            }

            if (isset($response['response']) && is_string($response['response'])) {
                return $response['response'];
            }

            if (isset($response['text']) && is_string($response['text'])) {
                return $response['text'];
            }
        }

        return null;
    }

    private function isValidAiResponse(?string $response): bool
    {
        if ($response === null || trim($response) === '') {
            return false;
        }

        $badMessages = [
            'L\'assistant IA est temporairement indisponible',
            'ERREUR OPENAI',
            'ERREUR Gemini',
            'insufficient_quota',
            'quota',
        ];

        foreach ($badMessages as $badMessage) {
            if (str_contains($response, $badMessage)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{
     *     destination?: string|null,
     *     budget?: float|null,
     *     days?: int|null
     * } $analysis
     * @param list<array{
     *     titre: string,
     *     destination: string,
     *     prix: float|int|string
     * }> $voyagesContext
     */
    private function buildPrompt(string $message, array $analysis, array $voyagesContext): string
    {
        $destination = $analysis['destination'] ?? 'non précisée';
        $budget = $analysis['budget'] ?? 'non précisé';
        $days = $analysis['days'] ?? 3;

        $voyagesText = '';

        foreach ($voyagesContext as $voyage) {
            $voyagesText .= '- ' . $voyage['titre'] . ' | ' . $voyage['destination'] . ' | ' . $voyage['prix'] . " TND\n";
        }

        return "
Tu es Horozia AI, assistant intelligent d'une agence de voyage.

Règles :
- Réponds dans la même langue que l'utilisateur.
- Donne un programme touristique clair et bien structuré.
- Si la durée n'est pas donnée, propose un programme de 3 jours.
- Donne monuments, musées, quartiers, activités et gastronomie.
- Ne crée jamais de faux prix.
- Ne crée jamais de faux voyages.

Contexte :
Destination : {$destination}
Budget : {$budget}
Durée : {$days}

Voyages disponibles chez Horozia :
{$voyagesText}

Question utilisateur :
{$message}
";
    }

    /**
     * @param array{
     *     destination?: string|null,
     *     budget?: float|null,
     *     days?: int|null
     * } $analysis
     */
    private function localSmartFallback(array $analysis): string
    {
        $destination = $analysis['destination'] ?? null;
        $days = (int) ($analysis['days'] ?? 3);

        if ($days <= 0) {
            $days = 3;
        }

        if ($destination === 'paris') {
            return "🇫🇷 Programme de {$days} jours à Paris :

Jour 1 :
- Tour Eiffel
- Champs-Élysées
- Arc de Triomphe
- Balade près de la Seine

Jour 2 :
- Musée du Louvre
- Jardin des Tuileries
- Montmartre
- Basilique du Sacré-Cœur

Jour 3 :
- Château de Versailles
- Quartier Latin
- Croisière sur la Seine
- Dîner dans un restaurant français

Bon voyage avec Horozia ✈️";
        }

        if ($destination === 'rome') {
            return "🇮🇹 Programme de {$days} jours à Rome :

Jour 1 :
- Colisée
- Forum Romain
- Mont Palatin
- Fontaine de Trevi

Jour 2 :
- Vatican
- Basilique Saint-Pierre
- Chapelle Sixtine
- Château Saint-Ange

Jour 3 :
- Panthéon
- Piazza Navona
- Trastevere
- Dîner italien traditionnel

Bon voyage avec Horozia ";
        }

        return "Je peux vous proposer un programme personnalisé. Indiquez une destination comme Paris, Rome, Barcelone ou Istanbul, ainsi que votre budget et la durée du séjour.";
    }
}