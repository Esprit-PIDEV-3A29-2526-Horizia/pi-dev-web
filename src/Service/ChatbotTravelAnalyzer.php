<?php

namespace App\Service;

class ChatbotTravelAnalyzer
{
    /**
     * @return array{
     *     destination: string|null,
     *     budget: float|null,
     *     days: int|null
     * }
     */
    public function analyze(string $message): array
    {
        $text = mb_strtolower($message);

        return [
            'destination' => $this->extractDestination($text),
            'budget' => $this->extractBudget($text),
            'days' => $this->extractDays($text),
        ];
    }

    private function extractDestination(string $text): ?string
    {
        $destinations = [
            'rome', 'paris', 'barcelone', 'madrid', 'londres',
            'istanbul', 'dubai', 'athenes', 'athens', 'milan',
            'venise', 'florence', 'tunis', 'djerba', 'sousse',
            'monastir', 'bali', 'tokyo', 'new york',
        ];

        foreach ($destinations as $destination) {
            if (str_contains($text, $destination)) {
                return $destination;
            }
        }

        return null;
    }

    private function extractBudget(string $text): ?float
    {
        if (preg_match('/(\d+)\s*(tnd|dt|dinars|eur|euro|€|usd|\$)/iu', $text, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function extractDays(string $text): ?int
    {
        if (preg_match('/(\d+)\s*(jours|jour|jrs|jr|days|day|أيام|ايام|نهارات)/iu', $text, $matches)) {
            return max(1, min((int) $matches[1], 15));
        }

        return null;
    }
}