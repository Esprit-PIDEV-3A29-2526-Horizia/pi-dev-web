<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Logement
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $type;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(type: "string", length: 500)]
    private string $image;

    #[ORM\Column(type: "string", length: 255)]
    private string $adresse;

    #[ORM\Column(type: "integer")]
    private int $capacite;

    #[ORM\Column(type: "string", length: 255)]
    private string $equipement;

    #[ORM\Column(type: "string")]
    private string $tarif_nuit;

    #[ORM\Column(type: "boolean")]
    private bool $disponibilite;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getAdresse()
    {
        return $this->adresse;
    }

    public function setAdresse($value)
    {
        $this->adresse = $value;
    }

    public function getCapacite()
    {
        return $this->capacite;
    }

    public function setCapacite($value)
    {
        $this->capacite = $value;
    }

    public function getEquipement()
    {
        return $this->equipement;
    }

    public function setEquipement($value)
    {
        $this->equipement = $value;
    }

    public function getTarif_nuit()
    {
        return $this->tarif_nuit;
    }

    public function setTarif_nuit($value)
    {
        $this->tarif_nuit = $value;
    }

    public function getDisponibilite()
    {
        return $this->disponibilite;
    }

    public function setDisponibilite($value)
    {
        $this->disponibilite = $value;
    }
}
