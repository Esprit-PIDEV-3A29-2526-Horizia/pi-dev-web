<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Favoris
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $utilisateur_id;

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $publication_id;

    public function getUtilisateur_id()
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateur_id($value)
    {
        $this->utilisateur_id = $value;
    }

    public function getPublication_id()
    {
        return $this->publication_id;
    }

    public function setPublication_id($value)
    {
        $this->publication_id = $value;
    }
}
