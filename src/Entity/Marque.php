<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Marque
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_marque;

    #[ORM\Column(type: "string", length: 50)]
    private string $nom_marque;

    #[ORM\Column(type: "string", length: 255)]
    private string $logo;

    public function getId_marque()
    {
        return $this->id_marque;
    }

    public function setId_marque($value)
    {
        $this->id_marque = $value;
    }

    public function getNom_marque()
    {
        return $this->nom_marque;
    }

    public function setNom_marque($value)
    {
        $this->nom_marque = $value;
    }

    public function getLogo()
    {
        return $this->logo;
    }

    public function setLogo($value)
    {
        $this->logo = $value;
    }
}
