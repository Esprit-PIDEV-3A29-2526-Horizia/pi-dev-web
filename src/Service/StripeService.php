<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeService
{
    public function __construct(private string $stripeSecretKey)
    {
        Stripe::setApiKey($stripeSecretKey);
    }

    public function createCheckoutSession(float $amount, string $currency, string $successUrl, string $cancelUrl): Session
    {
        return Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => ['name' => 'Réservation de logement'],
                    'unit_amount' => (int)($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }
}