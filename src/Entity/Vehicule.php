<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vehicule')]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_vehicule', type: 'integer')]
    private ?int $idVehicule = null;

    #[ORM\Column(name: 'immatriculation', type: 'string', length: 20)]
    private ?string $immatriculation = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    #[ORM\JoinColumn(name: 'id_modele', referencedColumnName: 'id_modele', nullable: false)]
    private ?Modele $modele = null;

    #[ORM\Column(name: 'annee', type: 'integer')]
    private ?int $annee = null;

    #[ORM\Column(name: 'carburant', type: 'string', length: 20)]
    private ?string $carburant = null;

    #[ORM\Column(name: 'couleur', type: 'string', length: 30, nullable: true)]
    private ?string $couleur = null;

    #[ORM\Column(name: 'kilometrage', type: 'integer')]
    private ?int $kilometrage = null;

    #[ORM\Column(name: 'etat', type: 'string', length: 20)]
    private string $etat = 'disponible';

    #[ORM\Column(name: 'prix_par_jour', type: 'decimal', precision: 10, scale: 3)]
    private ?string $prixParJour = null;

    #[ORM\Column(name: 'photo', type: 'string', length: 255, nullable: true)]
    private ?string $photo = null;

    /** @var Collection<int, Location> */
    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'vehicule')]
    private Collection $locations;

    public function __construct()
    {
        $this->locations = new ArrayCollection();
    }

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

    public function getModele(): ?Modele
    {
        return $this->modele;
    }

    public function setModele(?Modele $modele): static
    {
        $this->modele = $modele;
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

    public function setKilometrage(int $kilometrage): static
    {
        $this->kilometrage = $kilometrage;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
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

    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
            $location->setVehicule($this);
        }
        return $this;
    }

    public function removeLocation(Location $location): static
    {
        if ($this->locations->removeElement($location)) {
            if ($location->getVehicule() === $this) {
                $location->setVehicule(null);
            }
        }
        return $this;
    }
    public function __toString(): string
{
    return $this->immatriculation ?? '';
}
}