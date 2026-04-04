<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Location
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_location;

    #[ORM\Column(type: "integer")]
    private int $id_vehicule;

    #[ORM\Column(type: "string", length: 100)]
    private string $client_nom_complet;

    #[ORM\Column(type: "string", length: 15)]
    private string $client_telephone;

    #[ORM\Column(type: "string", length: 20)]
    private string $client_cin;

    #[ORM\Column(type: "string", length: 255)]
    private string $client_adresse;

    #[ORM\Column(type: "string", length: 100)]
    private string $client_ville;

    #[ORM\Column(type: "string", length: 10)]
    private string $client_code_postal;

    #[ORM\Column(type: "float")]
    private float $client_latitude;

    #[ORM\Column(type: "float")]
    private float $client_longitude;

    #[ORM\Column(type: "string", length: 20)]
    private string $client_permis_numero;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_debut;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_fin_prevue;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_fin_reelle;

    #[ORM\Column(type: "integer")]
    private int $kilometrage_debut;

    #[ORM\Column(type: "integer")]
    private int $kilometrage_retour;

    #[ORM\Column(type: "float")]
    private float $prix_par_jour;

    #[ORM\Column(type: "float")]
    private float $montant_total;

    #[ORM\Column(type: "float")]
    private float $avance;

    #[ORM\Column(type: "float")]
    private float $reste_a_payer;

    #[ORM\Column(type: "string")]
    private string $statut;

    #[ORM\Column(type: "text")]
    private string $notes;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updated_at;

    public function getId_location()
    {
        return $this->id_location;
    }

    public function setId_location($value)
    {
        $this->id_location = $value;
    }

    public function getId_vehicule()
    {
        return $this->id_vehicule;
    }

    public function setId_vehicule($value)
    {
        $this->id_vehicule = $value;
    }

    public function getClient_nom_complet()
    {
        return $this->client_nom_complet;
    }

    public function setClient_nom_complet($value)
    {
        $this->client_nom_complet = $value;
    }

    public function getClient_telephone()
    {
        return $this->client_telephone;
    }

    public function setClient_telephone($value)
    {
        $this->client_telephone = $value;
    }

    public function getClient_cin()
    {
        return $this->client_cin;
    }

    public function setClient_cin($value)
    {
        $this->client_cin = $value;
    }

    public function getClient_adresse()
    {
        return $this->client_adresse;
    }

    public function setClient_adresse($value)
    {
        $this->client_adresse = $value;
    }

    public function getClient_ville()
    {
        return $this->client_ville;
    }

    public function setClient_ville($value)
    {
        $this->client_ville = $value;
    }

    public function getClient_code_postal()
    {
        return $this->client_code_postal;
    }

    public function setClient_code_postal($value)
    {
        $this->client_code_postal = $value;
    }

    public function getClient_latitude()
    {
        return $this->client_latitude;
    }

    public function setClient_latitude($value)
    {
        $this->client_latitude = $value;
    }

    public function getClient_longitude()
    {
        return $this->client_longitude;
    }

    public function setClient_longitude($value)
    {
        $this->client_longitude = $value;
    }

    public function getClient_permis_numero()
    {
        return $this->client_permis_numero;
    }

    public function setClient_permis_numero($value)
    {
        $this->client_permis_numero = $value;
    }

    public function getDate_debut()
    {
        return $this->date_debut;
    }

    public function setDate_debut($value)
    {
        $this->date_debut = $value;
    }

    public function getDate_fin_prevue()
    {
        return $this->date_fin_prevue;
    }

    public function setDate_fin_prevue($value)
    {
        $this->date_fin_prevue = $value;
    }

    public function getDate_fin_reelle()
    {
        return $this->date_fin_reelle;
    }

    public function setDate_fin_reelle($value)
    {
        $this->date_fin_reelle = $value;
    }

    public function getKilometrage_debut()
    {
        return $this->kilometrage_debut;
    }

    public function setKilometrage_debut($value)
    {
        $this->kilometrage_debut = $value;
    }

    public function getKilometrage_retour()
    {
        return $this->kilometrage_retour;
    }

    public function setKilometrage_retour($value)
    {
        $this->kilometrage_retour = $value;
    }

    public function getPrix_par_jour()
    {
        return $this->prix_par_jour;
    }

    public function setPrix_par_jour($value)
    {
        $this->prix_par_jour = $value;
    }

    public function getMontant_total()
    {
        return $this->montant_total;
    }

    public function setMontant_total($value)
    {
        $this->montant_total = $value;
    }

    public function getAvance()
    {
        return $this->avance;
    }

    public function setAvance($value)
    {
        $this->avance = $value;
    }

    public function getReste_a_payer()
    {
        return $this->reste_a_payer;
    }

    public function setReste_a_payer($value)
    {
        $this->reste_a_payer = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes($value)
    {
        $this->notes = $value;
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
