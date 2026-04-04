<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Voyage
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $titre;

    #[ORM\Column(type: "string", length: 255)]
    private string $destination;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "float")]
    private float $prix;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_depart;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_retour;

    #[ORM\Column(type: "string", length: 255)]
    private string $image_url;

    #[ORM\Column(type: "integer")]
    private int $id_categorie;

    #[ORM\Column(type: "integer")]
    private int $places_total;

    #[ORM\Column(type: "integer")]
    private int $places_restantes;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($value)
    {
        $this->titre = $value;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setDestination($value)
    {
        $this->destination = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getPrix()
    {
        return $this->prix;
    }

    public function setPrix($value)
    {
        $this->prix = $value;
    }

    public function getDate_depart()
    {
        return $this->date_depart;
    }

    public function setDate_depart($value)
    {
        $this->date_depart = $value;
    }

    public function getDate_retour()
    {
        return $this->date_retour;
    }

    public function setDate_retour($value)
    {
        $this->date_retour = $value;
    }

    public function getImage_url()
    {
        return $this->image_url;
    }

    public function setImage_url($value)
    {
        $this->image_url = $value;
    }

    public function getId_categorie()
    {
        return $this->id_categorie;
    }

    public function setId_categorie($value)
    {
        $this->id_categorie = $value;
    }

    public function getPlaces_total()
    {
        return $this->places_total;
    }

    public function setPlaces_total($value)
    {
        $this->places_total = $value;
    }

    public function getPlaces_restantes()
    {
        return $this->places_restantes;
    }

    public function setPlaces_restantes($value)
    {
        $this->places_restantes = $value;
    }
}
