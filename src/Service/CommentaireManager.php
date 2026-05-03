<?php

namespace App\Service;

use App\Entity\Commentaire;

class CommentaireManager
{
    public function validate(Commentaire $commentaire): bool
    {
        $contenu = $commentaire->getContenu() ?? '';
        if (empty(trim($contenu))) {
            throw new \InvalidArgumentException('Le commentaire ne peut pas être vide.');
        }
        if (strlen(trim($contenu)) < 2) {
            throw new \InvalidArgumentException('Le commentaire doit comporter au moins 2 caracteres.');
        }
        if (strlen($contenu) > 500) {
            throw new \InvalidArgumentException('Le commentaire ne peut pas depasser 500 caracteres.');
        }
        return true;
    }

    public function validateAuteur(Commentaire $commentaire): bool
    {
        $auteur = $commentaire->getAuteur() ?? '';
        if (empty(trim($auteur))) {
            throw new \InvalidArgumentException("L'auteur du commentaire est obligatoire.");
        }
        return true;
    }
}