<?php

namespace App\Service;

use App\Entity\Reservation;

class ReservationManager
{
    public function validate(Reservation $reservation): bool
    {
        if ($reservation->getNbrPersonnes() === null || $reservation->getNbrPersonnes() <= 0) {
            throw new \InvalidArgumentException('Au moins 1 personne requise.');
        }

        if ($reservation->getVoyage() === null) {
            throw new \InvalidArgumentException('Voyage obligatoire.');
        }

        if ($reservation->getVoyage()->getPlacesRestantes() === null) {
            throw new \InvalidArgumentException('Places restantes non définies.');
        }

        if ($reservation->getNbrPersonnes() > $reservation->getVoyage()->getPlacesRestantes()) {
            throw new \InvalidArgumentException('Dépassement des places.');
        }

        return true;
    }

    public function canEdit(Reservation $reservation): bool
    {
        if ($reservation->getStatut() === 'CONFIRMEE') {
            throw new \InvalidArgumentException('Modification interdite.');
        }

        return true;
    }

    public function canConfirm(Reservation $reservation): bool
    {
        if ($reservation->getStatut() === 'ANNULEE') {
            throw new \InvalidArgumentException('Impossible de confirmer.');
        }

        return true;
    }

    public function calculatePrice(Reservation $reservation): float
    {
        if ($reservation->getVoyage() === null) {
            throw new \InvalidArgumentException('Voyage obligatoire.');
        }

        if ($reservation->getVoyage()->getPrix() === null) {
            throw new \InvalidArgumentException('Prix du voyage non défini.');
        }

        if ($reservation->getNbrPersonnes() === null || $reservation->getNbrPersonnes() <= 0) {
            throw new \InvalidArgumentException('Au moins 1 personne requise.');
        }

        return $reservation->getVoyage()->getPrix() * $reservation->getNbrPersonnes();
    }
}