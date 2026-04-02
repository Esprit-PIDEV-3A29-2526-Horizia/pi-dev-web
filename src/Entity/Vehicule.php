<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\VehiculeRepository;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
#[ORM\Table(name: 'vehicule')]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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
    private ?string $immatriculation = null;

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): self
    {
        $this->immatriculation = $immatriculation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_modele = null;

    public function getId_modele(): ?int
    {
        return $this->id_modele;
    }

    public function setId_modele(int $id_modele): self
    {
        $this->id_modele = $id_modele;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $annee = null;

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): self
    {
        $this->annee = $annee;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $carburant = null;

    public function getCarburant(): ?string
    {
        return $this->carburant;
    }

    public function setCarburant(string $carburant): self
    {
        $this->carburant = $carburant;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $couleur = null;

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $kilometrage = null;

    public function getKilometrage(): ?int
    {
        return $this->kilometrage;
    }

    public function setKilometrage(?int $kilometrage): self
    {
        $this->kilometrage = $kilometrage;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $etat = null;

    public function getEtat(): ?int
    {
        return $this->etat;
    }

    public function setEtat(?int $etat): self
    {
        $this->etat = $etat;
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $photo = null;

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
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

    public function getIdVehicule(): ?int
    {
        return $this->id_vehicule;
    }

    public function getIdModele(): ?int
    {
        return $this->id_modele;
    }

    public function setIdModele(int $id_modele): static
    {
        $this->id_modele = $id_modele;

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
