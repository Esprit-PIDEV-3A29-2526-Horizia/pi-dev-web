<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Vehicule
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_vehicule;

    #[ORM\Column(type: "string", length: 20)]
    private string $immatriculation;

    #[ORM\Column(type: "integer")]
    private int $id_modele;

    #[ORM\Column(type: "integer")]
    private int $annee;

    #[ORM\Column(type: "string")]
    private string $carburant;

    #[ORM\Column(type: "string", length: 30)]
    private string $couleur;

    #[ORM\Column(type: "integer")]
    private int $kilometrage;

    #[ORM\Column(type: "string")]
    private string $etat;

    #[ORM\Column(type: "float")]
    private float $prix_par_jour;

    #[ORM\Column(type: "string", length: 255)]
    private string $photo;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updated_at;

    public function getId_vehicule()
    {
        return $this->id_vehicule;
    }

    public function setId_vehicule($value)
    {
        $this->id_vehicule = $value;
    }

    public function getImmatriculation()
    {
        return $this->immatriculation;
    }

    public function setImmatriculation($value)
    {
        $this->immatriculation = $value;
    }

    public function getId_modele()
    {
        return $this->id_modele;
    }

    public function setId_modele($value)
    {
        $this->id_modele = $value;
    }

    public function getAnnee()
    {
        return $this->annee;
    }

    public function setAnnee($value)
    {
        $this->annee = $value;
    }

    public function getCarburant()
    {
        return $this->carburant;
    }

    public function setCarburant($value)
    {
        $this->carburant = $value;
    }

    public function getCouleur()
    {
        return $this->couleur;
    }

    public function setCouleur($value)
    {
        $this->couleur = $value;
    }

    public function getKilometrage()
    {
        return $this->kilometrage;
    }

    public function setKilometrage($value)
    {
        $this->kilometrage = $value;
    }

    public function getEtat()
    {
        return $this->etat;
    }

    public function setEtat($value)
    {
        $this->etat = $value;
    }

    public function getPrix_par_jour()
    {
        return $this->prix_par_jour;
    }

    public function setPrix_par_jour($value)
    {
        $this->prix_par_jour = $value;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

    public function setPhoto($value)
    {
        $this->photo = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getUpdated_at()
    {
        return $this->updated_at;
    }

    public function setUpdated_at($value)
    {
        $this->updated_at = $value;
    }
}
