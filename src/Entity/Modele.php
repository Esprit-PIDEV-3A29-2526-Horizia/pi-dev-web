<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Modele
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_modele;

    #[ORM\Column(type: "integer")]
    private int $id_marque;

    #[ORM\Column(type: "string", length: 80)]
    private string $nom_modele;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    public function getId_modele()
    {
        return $this->id_modele;
    }

    public function setId_modele($value)
    {
        $this->id_modele = $value;
    }

    public function getId_marque()
    {
        return $this->id_marque;
    }

    public function setId_marque($value)
    {
        $this->id_marque = $value;
    }

    public function getNom_modele()
    {
        return $this->nom_modele;
    }

    public function setNom_modele($value)
    {
        $this->nom_modele = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }
}
