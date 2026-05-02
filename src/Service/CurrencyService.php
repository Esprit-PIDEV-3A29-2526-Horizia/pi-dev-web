<?php

namespace App\Service;

class CurrencyService
{
    private const ALLOWED = ['TND', 'EUR', 'USD', 'CHF', 'JPY', 'AUD', 'IDR'];

    public function normalizeCurrency(?string $currency): string
    {
        $currency = strtoupper(trim((string) $currency));

        if (!in_array($currency, self::ALLOWED, true)) {
            return 'TND';
        }

        return $currency;
    }

    /**
 * @return array<int, string>
 */
public function getAllowedCurrencies(): array
    {
        return self::ALLOWED;
    }

    public function getSymbol(string $currency): string
    {
        return match ($currency) {
            'EUR' => '€',
            'USD' => '$',
            'CHF' => 'CHF',
            'JPY' => '¥',
            'AUD' => 'A$',
            'IDR' => 'Rp',
            default => 'DT',
        };
    }

    public function convert(float $amountTnd, string $currency): ?float
    {
        $currency = $this->normalizeCurrency($currency);

        if ($currency === 'TND') {
            return $amountTnd;
        }

        $rates = [
            'EUR' => 0.30,
            'USD' => 0.33,
            'CHF' => 0.29,
            'JPY' => 49.50,
            'AUD' => 0.50,
            'IDR' => 5200.00,
        ];

        if (!isset($rates[$currency])) {
            return null;
        }

        return round($amountTnd * $rates[$currency], 2);
    }
}