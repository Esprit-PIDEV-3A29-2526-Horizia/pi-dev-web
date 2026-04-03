<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Vehicule
 *
 * @ORM\Table(name="vehicule", uniqueConstraints={@ORM\UniqueConstraint(name="immatriculation", columns={"immatriculation"})}, indexes={@ORM\Index(name="id_modele", columns={"id_modele"}), @ORM\Index(name="idx_vehicule_etat", columns={"etat"})})
 * @ORM\Entity
 */
class Vehicule
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_vehicule", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idVehicule;

    /**
     * @var string
     *
     * @ORM\Column(name="immatriculation", type="string", length=20, nullable=false, options={"comment"="ex: 123 TUN 45"})
     */
    private $immatriculation;

    /**
     * @var int
     *
     * @ORM\Column(name="id_modele", type="integer", nullable=false)
     */
    private $idModele;

    /**
     * @var int
     *
     * @ORM\Column(name="annee", type="integer", nullable=false)
     */
    private $annee;

    /**
     * @var string
     *
     * @ORM\Column(name="carburant", type="string", length=0, nullable=false)
     */
    private $carburant;

    /**
     * @var string|null
     *
     * @ORM\Column(name="couleur", type="string", length=30, nullable=true)
     */
    private $couleur;

    /**
     * @var int|null
     *
     * @ORM\Column(name="kilometrage", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $kilometrage = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="etat", type="string", length=0, nullable=true, options={"default"="disponible"})
     */
    private $etat = 'disponible';

    /**
     * @var string
     *
     * @ORM\Column(name="prix_par_jour", type="decimal", precision=10, scale=3, nullable=false, options={"comment"="en TND"})
     */
    private $prixParJour;

    /**
     * @var string|null
     *
     * @ORM\Column(name="photo", type="string", length=255, nullable=true, options={"comment"="chemin ou URL"})
     */
    private $photo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt = 'CURRENT_TIMESTAMP';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt = 'CURRENT_TIMESTAMP';

    public function getIdVehicule(): ?int
    {
        return $this->idVehicule;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getIdModele(): ?int
    {
        return $this->idModele;
    }

    public function setIdModele(int $idModele): static
    {
        $this->idModele = $idModele;

        return $this;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;

        return $this;
    }

    public function getCarburant(): ?string
    {
        return $this->carburant;
    }

    public function setCarburant(string $carburant): static
    {
        $this->carburant = $carburant;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getKilometrage(): ?int
    {
        return $this->kilometrage;
    }

    public function setKilometrage(?int $kilometrage): static
    {
        $this->kilometrage = $kilometrage;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getPrixParJour(): ?string
    {
        return $this->prixParJour;
    }

    public function setPrixParJour(string $prixParJour): static
    {
        $this->prixParJour = $prixParJour;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


}
