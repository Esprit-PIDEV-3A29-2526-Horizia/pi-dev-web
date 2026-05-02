<?php

namespace App\Tests\Service;

use App\Entity\Reservation;
use App\Entity\Voyage;
use App\Service\ReservationManager;
use PHPUnit\Framework\TestCase;

class ReservationManagerTest extends TestCase
{
    private function createVoyage(): Voyage
    {
        $voyage = new Voyage();
        $voyage->setPrix(100);
        $voyage->setPlacesRestantes(5);

        return $voyage;
    }

    public function testValidReservation(): void
    {
        $reservation = new Reservation();
        $reservation->setVoyage($this->createVoyage());
        $reservation->setNbrPersonnes(2);

        $manager = new ReservationManager();

        $this->assertTrue($manager->validate($reservation));
    }

    public function testReservationWithoutPerson(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = new Reservation();
        $reservation->setVoyage($this->createVoyage());
        $reservation->setNbrPersonnes(0);

        $manager = new ReservationManager();
        $manager->validate($reservation);
    }

    public function testReservationExceedsPlaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = new Reservation();
        $reservation->setVoyage($this->createVoyage());
        $reservation->setNbrPersonnes(10);

        $manager = new ReservationManager();
        $manager->validate($reservation);
    }

    public function testCannotEditConfirmedReservation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = new Reservation();
        $reservation->setStatut('CONFIRMEE');

        $manager = new ReservationManager();
        $manager->canEdit($reservation);
    }

    public function testCannotConfirmCancelledReservation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = new Reservation();
        $reservation->setStatut('ANNULEE');

        $manager = new ReservationManager();
        $manager->canConfirm($reservation);
    }

    public function testCalculatePrice(): void
    {
        $reservation = new Reservation();
        $reservation->setVoyage($this->createVoyage());
        $reservation->setNbrPersonnes(3);

        $manager = new ReservationManager();

        $this->assertEquals(300, $manager->calculatePrice($reservation));
    }
}