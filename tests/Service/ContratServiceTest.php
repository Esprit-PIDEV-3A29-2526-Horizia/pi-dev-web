<?php

namespace App\Tests\Service;

use App\Service\ContratService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;


#[CoversClass(ContratService::class)]
class ContratServiceTest extends TestCase
{
    // RÈGLE 1 : durée minimale = 1 jour

    public function testNbJoursNormal(): void
    {
        $service = new ContratService();
        $debut   = new \DateTime('2025-06-01');
        $fin     = new \DateTime('2025-06-05');

        $this->assertEquals(4, $service->calculerNbJours($debut, $fin));
    }

    public function testNbJoursMinimum1(): void
    {
        $service = new ContratService();
        $date    = new \DateTime('2025-06-01');

        $this->assertEquals(1, $service->calculerNbJours($date, clone $date));
    }

    // RÈGLE 2 : montant base = prix/jour × jours

    public function testMontantBaseCalculCorrect(): void
    {
        $service = new ContratService();
        $debut   = new \DateTime('2025-06-01');
        $fin     = new \DateTime('2025-06-04');

        $this->assertEquals(150.0, $service->calculerMontantBase(50.0, $debut, $fin));
    }

    public function testMontantBaseAvecPrixZero(): void
    {
        $service = new ContratService();
        $debut   = new \DateTime('2025-06-01');
        $fin     = new \DateTime('2025-06-05');

        $this->assertEquals(0.0, $service->calculerMontantBase(0.0, $debut, $fin));
    }

    // RÈGLE 3 : solde jamais négatif

    public function testSoldeNormal(): void
    {
        $service = new ContratService();

        $this->assertEquals(150.0, $service->calculerSolde(400.0, 250.0));
    }

    public function testSoldeNonNegatifSiAvanceDepasse(): void
    {
        $service = new ContratService();

        $this->assertEquals(0.0, $service->calculerSolde(100.0, 200.0));
    }

    public function testSoldeNulSiAvanceEgaleTotal(): void
    {
        $service = new ContratService();

        $this->assertEquals(0.0, $service->calculerSolde(300.0, 300.0));
    }

    // RÈGLE 4 : montant formaté = 3 décimales + TND

    public function testFormaterMontantNormal(): void
    {
        $service = new ContratService();

        $this->assertEquals('150,000 TND', $service->formaterMontant(150.0));
    }

    public function testFormaterMontantAvecDecimales(): void
    {
        $service = new ContratService();

        $this->assertEquals('99,500 TND', $service->formaterMontant(99.5));
    }
}