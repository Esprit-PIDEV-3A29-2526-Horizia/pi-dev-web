<?php

namespace App\Service;

use App\Entity\Events;

class EventManager
{
    public function validate(Events $event): bool
    {
        if ($event->getPrix() < 0) {
            throw new \InvalidArgumentException('Le prix ne peut pas être négatif.');
        }

        if ($event->getDateFin() <= $event->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        if ($event->getCapaciteMax() < 1) {
            throw new \InvalidArgumentException('La capacité maximale doit être au moins 1.');
        }

        return true;
    }
}