<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\LocationRepository;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_location = null;

    public function getId_location(): ?int
    {
        return $this->id_location;
    }

    public function setId_location(int $id_location): self
    {
        $this->id_location = $id_location;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_vehicule = null;

    public function getId_vehicule(): ?int
    {
        return $this->id_vehicule;
    }

    public function setId_vehicule(int $id_vehicule): self
    {
        $this->id_vehicule = $id_vehicule;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $client_nom_complet = null;

    public function getClient_nom_complet(): ?string
    {
        return $this->client_nom_complet;
    }

    public function setClient_nom_complet(string $client_nom_complet): self
    {
        $this->client_nom_complet = $client_nom_complet;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $client_telephone = null;

    public function getClient_telephone(): ?string
    {
        return $this->client_telephone;
    }

    public function setClient_telephone(string $client_telephone): self
    {
        $this->client_telephone = $client_telephone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $client_cin = null;

    public function getClient_cin(): ?string
    {
        return $this->client_cin;
    }

    public function setClient_cin(?string $client_cin): self
    {
        $this->client_cin = $client_cin;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $client_adresse = null;

    public function getClient_adresse(): ?string
    {
        return $this->client_adresse;
    }

    public function setClient_adresse(?string $client_adresse): self
    {
        $this->client_adresse = $client_adresse;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $client_ville = null;

    public function getClient_ville(): ?string
    {
        return $this->client_ville;
    }

    public function setClient_ville(?string $client_ville): self
    {
        $this->client_ville = $client_ville;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $client_code_postal = null;

    public function getClient_code_postal(): ?string
    {
        return $this->client_code_postal;
    }

    public function setClient_code_postal(?string $client_code_postal): self
    {
        $this->client_code_postal = $client_code_postal;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $client_latitude = null;

    public function getClient_latitude(): ?float
    {
        return $this->client_latitude;
    }

    public function setClient_latitude(?float $client_latitude): self
    {
        $this->client_latitude = $client_latitude;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $client_longitude = null;

    public function getClient_longitude(): ?float
    {
        return $this->client_longitude;
    }

    public function setClient_longitude(?float $client_longitude): self
    {
        $this->client_longitude = $client_longitude;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $client_permis_numero = null;

    public function getClient_permis_numero(): ?string
    {
        return $this->client_permis_numero;
    }

    public function setClient_permis_numero(?string $client_permis_numero): self
    {
        $this->client_permis_numero = $client_permis_numero;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_debut = null;

    public function getDate_debut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDate_debut(\DateTimeInterface $date_debut): self
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_fin_prevue = null;

    public function getDate_fin_prevue(): ?\DateTimeInterface
    {
        return $this->date_fin_prevue;
    }

    public function setDate_fin_prevue(\DateTimeInterface $date_fin_prevue): self
    {
        $this->date_fin_prevue = $date_fin_prevue;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_fin_reelle = null;

    public function getDate_fin_reelle(): ?\DateTimeInterface
    {
        return $this->date_fin_reelle;
    }

    public function setDate_fin_reelle(?\DateTimeInterface $date_fin_reelle): self
    {
        $this->date_fin_reelle = $date_fin_reelle;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $kilometrage_debut = null;

    public function getKilometrage_debut(): ?int
    {
        return $this->kilometrage_debut;
    }

    public function setKilometrage_debut(int $kilometrage_debut): self
    {
        $this->kilometrage_debut = $kilometrage_debut;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $kilometrage_retour = null;

    public function getKilometrage_retour(): ?int
    {
        return $this->kilometrage_retour;
    }

    public function setKilometrage_retour(?int $kilometrage_retour): self
    {
        $this->kilometrage_retour = $kilometrage_retour;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $prix_par_jour = null;

    public function getPrix_par_jour(): ?float
    {
        return $this->prix_par_jour;
    }

    public function setPrix_par_jour(float $prix_par_jour): self
    {
        $this->prix_par_jour = $prix_par_jour;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $montant_total = null;

    public function getMontant_total(): ?float
    {
        return $this->montant_total;
    }

    public function setMontant_total(float $montant_total): self
    {
        $this->montant_total = $montant_total;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $avance = null;

    public function getAvance(): ?float
    {
        return $this->avance;
    }

    public function setAvance(?float $avance): self
    {
        $this->avance = $avance;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $reste_a_payer = null;

    public function getReste_a_payer(): ?float
    {
        return $this->reste_a_payer;
    }

    public function setReste_a_payer(?float $reste_a_payer): self
    {
        $this->reste_a_payer = $reste_a_payer;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $updated_at = null;

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getIdLocation(): ?int
    {
        return $this->id_location;
    }

    public function getIdVehicule(): ?int
    {
        return $this->id_vehicule;
    }

    public function setIdVehicule(int $id_vehicule): static
    {
        $this->id_vehicule = $id_vehicule;

        return $this;
    }

    public function getClientNomComplet(): ?string
    {
        return $this->client_nom_complet;
    }

    public function setClientNomComplet(string $client_nom_complet): static
    {
        $this->client_nom_complet = $client_nom_complet;

        return $this;
    }

    public function getClientTelephone(): ?string
    {
        return $this->client_telephone;
    }

    public function setClientTelephone(string $client_telephone): static
    {
        $this->client_telephone = $client_telephone;

        return $this;
    }

    public function getClientCin(): ?string
    {
        return $this->client_cin;
    }

    public function setClientCin(?string $client_cin): static
    {
        $this->client_cin = $client_cin;

        return $this;
    }

    public function getClientAdresse(): ?string
    {
        return $this->client_adresse;
    }

    public function setClientAdresse(?string $client_adresse): static
    {
        $this->client_adresse = $client_adresse;

        return $this;
    }

    public function getClientVille(): ?string
    {
        return $this->client_ville;
    }

    public function setClientVille(?string $client_ville): static
    {
        $this->client_ville = $client_ville;

        return $this;
    }

    public function getClientCodePostal(): ?string
    {
        return $this->client_code_postal;
    }

    public function setClientCodePostal(?string $client_code_postal): static
    {
        $this->client_code_postal = $client_code_postal;

        return $this;
    }

    public function getClientLatitude(): ?string
    {
        return $this->client_latitude;
    }

    public function setClientLatitude(?string $client_latitude): static
    {
        $this->client_latitude = $client_latitude;

        return $this;
    }

    public function getClientLongitude(): ?string
    {
        return $this->client_longitude;
    }

    public function setClientLongitude(?string $client_longitude): static
    {
        $this->client_longitude = $client_longitude;

        return $this;
    }

    public function getClientPermisNumero(): ?string
    {
        return $this->client_permis_numero;
    }

    public function setClientPermisNumero(?string $client_permis_numero): static
    {
        $this->client_permis_numero = $client_permis_numero;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    public function getDateFinPrevue(): ?\DateTime
    {
        return $this->date_fin_prevue;
    }

    public function setDateFinPrevue(\DateTime $date_fin_prevue): static
    {
        $this->date_fin_prevue = $date_fin_prevue;

        return $this;
    }

    public function getDateFinReelle(): ?\DateTime
    {
        return $this->date_fin_reelle;
    }

    public function setDateFinReelle(?\DateTime $date_fin_reelle): static
    {
        $this->date_fin_reelle = $date_fin_reelle;

        return $this;
    }

    public function getKilometrageDebut(): ?int
    {
        return $this->kilometrage_debut;
    }

    public function setKilometrageDebut(int $kilometrage_debut): static
    {
        $this->kilometrage_debut = $kilometrage_debut;

        return $this;
    }

    public function getKilometrageRetour(): ?int
    {
        return $this->kilometrage_retour;
    }

    public function setKilometrageRetour(?int $kilometrage_retour): static
    {
        $this->kilometrage_retour = $kilometrage_retour;

        return $this;
    }

    public function getPrixParJour(): ?string
    {
        return $this->prix_par_jour;
    }

    public function setPrixParJour(string $prix_par_jour): static
    {
        $this->prix_par_jour = $prix_par_jour;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montant_total;
    }

    public function setMontantTotal(string $montant_total): static
    {
        $this->montant_total = $montant_total;

        return $this;
    }

    public function getResteAPayer(): ?string
    {
        return $this->reste_a_payer;
    }

    public function setResteAPayer(?string $reste_a_payer): static
    {
        $this->reste_a_payer = $reste_a_payer;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTime $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

}
