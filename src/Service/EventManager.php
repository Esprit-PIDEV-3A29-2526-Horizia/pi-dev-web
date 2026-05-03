<?php

namespace App\Service;

use App\Entity\Events;
use App\Repository\EventRepository;
use InvalidArgumentException;

class EventManager
{
    public function validate(Events $event) :bool
    {
        //Le prix ne peut pas être négatif
        if ($event->getPrix() < 0) {
            throw new InvalidArgumentException('Le prix ne peut pas être négatif.');
        }

        //La date de fin doit être postérieure à la date début
        if ($event->getDateFin() <= $event->getDateDebut()){
            throw new InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        //La capacité maximale doit être au moins égale à 1
        if ($event->getCapaciteMax() < 1) {
            throw new InvalidArgumentException('La capacité maximale doit être au moins égale à 1.');
        }

        return true;
    }
}