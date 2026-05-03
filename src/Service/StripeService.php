<?php

namespace App\Service;

use Stripe\StripeClient;

class StripeService
{
    private StripeClient $stripe;

    public function __construct(string $stripeSecretKey)
    {
        $this->stripe = new StripeClient($stripeSecretKey);
    }

    /**
     * @param array<string, string> $metadata
     */
    public function createCheckoutSession(
        string $productName,
        int $amountInMinorUnit,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): string {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $productName,
                    ],
                    'unit_amount' => $amountInMinorUnit,
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);

        return $session->url ?? '';
    }
}