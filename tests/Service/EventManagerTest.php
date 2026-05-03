<?php

namespace App\Tests\Service;

use App\Entity\Events;
use App\Service\EventManager;
use PHPUnit\Framework\TestCase;

class EventManagerTest extends TestCase
{
    private EventManager $eventManager;

    protected function setUp(): void
    {
        $this->eventManager = new EventManager();
    }

    public function testValidEvent(): void
    {
        $event = new Events();
        $event->setPrix('100');
        $event->setDateDebut(new \DateTime('2026-06-01'));
        $event->setDateFin(new \DateTime('2026-06-10'));
        $event->setCapaciteMax(50);

        $result = $this->eventManager->validate($event);
        $this->assertTrue($result);
    }

    public function testEventWithNegativePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix ne peut pas être négatif.');

        $event = new Events();
        $event->setPrix('-50');
        $event->setDateDebut(new \DateTime('2026-06-01'));
        $event->setDateFin(new \DateTime('2026-06-10'));
        $event->setCapaciteMax(50);

        $this->eventManager->validate($event);
    }

    public function testEventWithInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $event = new Events();
        $event->setPrix('100');
        $event->setDateDebut(new \DateTime('2026-06-10'));
        $event->setDateFin(new \DateTime('2026-06-01'));
        $event->setCapaciteMax(50);

        $this->eventManager->validate($event);
    }

    public function testEventWithInvalidCapacity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La capacité maximale doit être au moins 1.');

        $event = new Events();
        $event->setPrix('100');
        $event->setDateDebut(new \DateTime('2026-06-01'));
        $event->setDateFin(new \DateTime('2026-06-10'));
        $event->setCapaciteMax(0);

        $this->eventManager->validate($event);
    }
}