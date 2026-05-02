<?php

namespace App\Service;

use App\Entity\Publication;

class PublicationManager
{
    /**
     * Valide une publication selon les regles metier
     * @throws \InvalidArgumentException
     */
    public function validate(Publication $publication): bool
    {
        // Regle 1 : Titre non vide
        $titre = $publication->getTitre() ?? '';
        if (empty(trim($titre))) {
            throw new \InvalidArgumentException('Le titre de la publication est obligatoire.');
        }

        // Regle 2 : Titre d'au moins 3 caracteres
        if (strlen(trim($titre)) < 3) {
            throw new \InvalidArgumentException('Le titre doit comporter au moins 3 caracteres.');
        }

        // Regle 3 : Description non vide
        $description = $publication->getDescription() ?? '';
        if (empty(trim($description))) {
            throw new \InvalidArgumentException('La description est obligatoire.');
        }

        // Regle 4 : Description d'au moins 10 caracteres
        if (strlen(trim($description)) < 10) {
            throw new \InvalidArgumentException('La description doit comporter au moins 10 caracteres.');
        }

        return true;
    }
}