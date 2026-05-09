<?php
// src/Service/ReservationlogManager.php

namespace App\Service;

use App\Entity\Reservationlog;
use InvalidArgumentException;

class ReservationlogManager
{
    /**
     * Valide les règles métier d'une réservation de logement
     * 
     * @throws InvalidArgumentException
     */
    public function validate(Reservationlog $reservation): bool
    {
        // Règle 1 : Les dates doivent être valides
        $dateDebut = $reservation->getDateDebut();
        $dateFin = $reservation->getDateFin();

        if ($dateDebut === null || $dateFin === null) {
            throw new InvalidArgumentException('Les dates de début et de fin sont obligatoires.');
        }

        if ($dateDebut > $dateFin) {
            throw new InvalidArgumentException('La date de début ne peut pas être postérieure à la date de fin.');
        }

        // Règle 2 : Le montant doit être supérieur à zéro
        $montant = $reservation->getMontant();
        if ($montant === null || $montant <= 0) {
            throw new InvalidArgumentException('Le montant doit être supérieur à zéro.');
        }

        // Règle 3 : Le nombre d'adultes doit être au moins 1
        $adultes = $reservation->getAdultes();
        if ($adultes < 1) {
            throw new InvalidArgumentException('Le nombre d\'adultes doit être au moins 1.');
        }

        // Règle 4 : Le nombre de chambres doit être au moins 1
        $chambres = $reservation->getNombreChambres();
        if ($chambres < 1) {
            throw new InvalidArgumentException('Le nombre de chambres doit être au moins 1.');
        }

        return true;
    }
}