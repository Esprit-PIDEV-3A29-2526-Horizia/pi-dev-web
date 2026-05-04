<?php
// tests/Service/ReservationlogManagerTest.php

namespace App\Tests\Service;

use App\Entity\Reservationlog;
use App\Service\ReservationlogManager;
use PHPUnit\Framework\TestCase;

class ReservationlogManagerTest extends TestCase
{
    /**
     * Test 1 : Réservation valide
     */
    public function testValidReservation(): void
    {
        $reservation = new Reservationlog();
        $reservation->setDateDebut(new \DateTime('2025-05-10'));
        $reservation->setDateFin(new \DateTime('2025-05-15'));
        $reservation->setMontant(500.00);
        $reservation->setAdultes(2);
        $reservation->setNombreChambres(1);

        $manager = new ReservationlogManager();

        $this->assertTrue($manager->validate($reservation));
    }

    /**
     * Test 2 : Réservation sans date de début (invalide)
     */
    public function testReservationWithoutStartDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les dates de début et de fin sont obligatoires.');

        $reservation = new Reservationlog();
        // Utiliser une date vide au lieu de null
        $reservation->setDateDebut(new \DateTime('2025-05-10'));
        $reservation->setDateFin(new \DateTime('2025-05-15'));
        $reservation->setMontant(500.00);
        $reservation->setAdultes(2);
        $reservation->setNombreChambres(1);
        
        // Simuler une date manquante en la mettant à null via reflection
        $reflection = new \ReflectionClass($reservation);
        $property = $reflection->getProperty('date_debut');
        $property->setAccessible(true);
        $property->setValue($reservation, null);

        $manager = new ReservationlogManager();
        $manager->validate($reservation);
    }

    /**
     * Test 3 : Date de début postérieure à date de fin (invalide)
     */
    public function testReservationWithInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de début ne peut pas être postérieure à la date de fin.');

        $reservation = new Reservationlog();
        $reservation->setDateDebut(new \DateTime('2025-05-15'));
        $reservation->setDateFin(new \DateTime('2025-05-10'));
        $reservation->setMontant(500.00);
        $reservation->setAdultes(2);
        $reservation->setNombreChambres(1);

        $manager = new ReservationlogManager();
        $manager->validate($reservation);
    }

    /**
     * Test 4 : Réservation avec montant nul (invalide)
     */
    public function testReservationWithNullMontant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le montant doit être supérieur à zéro.');

        $reservation = new Reservationlog();
        $reservation->setDateDebut(new \DateTime('2025-05-10'));
        $reservation->setDateFin(new \DateTime('2025-05-15'));
        $reservation->setAdultes(2);
        $reservation->setNombreChambres(1);
        
        // Simuler un montant null via reflection
        $reflection = new \ReflectionClass($reservation);
        $property = $reflection->getProperty('montant');
        $property->setAccessible(true);
        $property->setValue($reservation, null);

        $manager = new ReservationlogManager();
        $manager->validate($reservation);
    }

    /**
     * Test 5 : Réservation avec montant négatif (invalide)
     */
    public function testReservationWithNegativeMontant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le montant doit être supérieur à zéro.');

        $reservation = new Reservationlog();
        $reservation->setDateDebut(new \DateTime('2025-05-10'));
        $reservation->setDateFin(new \DateTime('2025-05-15'));
        $reservation->setMontant(-100.00);
        $reservation->setAdultes(2);
        $reservation->setNombreChambres(1);

        $manager = new ReservationlogManager();
        $manager->validate($reservation);
    }

    /**
     * Test 6 : Réservation avec nombre d'adultes invalide (0)
     */
    public function testReservationWithZeroAdultes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre d\'adultes doit être au moins 1.');

        $reservation = new Reservationlog();
        $reservation->setDateDebut(new \DateTime('2025-05-10'));
        $reservation->setDateFin(new \DateTime('2025-05-15'));
        $reservation->setMontant(500.00);
        $reservation->setAdultes(0);
        $reservation->setNombreChambres(1);

        $manager = new ReservationlogManager();
        $manager->validate($reservation);
    }

    /**
     * Test 7 : Réservation avec nombre de chambres invalide (0)
     */
    public function testReservationWithZeroChambres(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de chambres doit être au moins 1.');

        $reservation = new Reservationlog();
        $reservation->setDateDebut(new \DateTime('2025-05-10'));
        $reservation->setDateFin(new \DateTime('2025-05-15'));
        $reservation->setMontant(500.00);
        $reservation->setAdultes(2);
        $reservation->setNombreChambres(0);

        $manager = new ReservationlogManager();
        $manager->validate($reservation);
    }
}