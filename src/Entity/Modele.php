<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'modele')]
class Modele
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_modele', type: 'integer')]
    private int $idModele = 0;

    #[ORM\Column(name: 'nom_modele', type: 'string', length: 80)]
    private ?string $nomModele = null;

    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'modeles')]
    #[ORM\JoinColumn(name: 'id_marque', referencedColumnName: 'id_marque', nullable: false)]
    private ?Marque $marque = null;

    /** @var Collection<int, Vehicule> */
    #[ORM\OneToMany(targetEntity: Vehicule::class, mappedBy: 'modele')]
    private Collection $vehicules;

    public function __construct()
    {
        $this->vehicules = new ArrayCollection();
    }

    public function getIdModele(): ?int
    {
        return $this->idModele;
    }

    public function getNomModele(): ?string
    {
        return $this->nomModele;
    }

    public function setNomModele(string $nomModele): static
    {
        $this->nomModele = $nomModele;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): static
    {
        $this->marque = $marque;
        return $this;
    }

    /**
     * @return Collection<int, Vehicule>
     */
    public function getVehicules(): Collection
    {
        return $this->vehicules;
    }

    public function addVehicule(Vehicule $vehicule): static
    {
        if (!$this->vehicules->contains($vehicule)) {
            $this->vehicules->add($vehicule);
            $vehicule->setModele($this);
        }
        return $this;
    }

    public function removeVehicule(Vehicule $vehicule): static
    {
        if ($this->vehicules->removeElement($vehicule)) {
            if ($vehicule->getModele() === $this) {
                $vehicule->setModele(null);
            }
        }
        return $this;
    }
    public function __toString(): string
{
    $marqueNom = $this->marque ? $this->marque->getNomMarque() : '';
    return trim($marqueNom . ' ' . ($this->nomModele ?? ''));
}
}