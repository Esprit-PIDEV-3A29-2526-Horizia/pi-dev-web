<?php

namespace App\Service;

use App\Entity\Publication;

class PublicationManager
{
    public function validate(Publication $publication): bool
    {
        $titre = $publication->getTitre() ?? '';
        if (empty(trim($titre))) {
            throw new \InvalidArgumentException('Le titre de la publication est obligatoire.');
        }
        if (strlen(trim($titre)) < 3) {
            throw new \InvalidArgumentException('Le titre doit comporter au moins 3 caracteres.');
        }
        $description = $publication->getDescription() ?? '';
        if (empty(trim($description))) {
            throw new \InvalidArgumentException('La description est obligatoire.');
        }
        if (strlen(trim($description)) < 10) {
            throw new \InvalidArgumentException('La description doit comporter au moins 10 caracteres.');
        }
        return true;
    }
}