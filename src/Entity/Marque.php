<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'marque')]
class Marque
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_marque', type: 'integer')]
    private ?int $idMarque = null;

    #[ORM\Column(name: 'nom_marque', type: 'string', length: 50)]
    private ?string $nomMarque = null;

    #[ORM\Column(name: 'logo', type: 'string', length: 255, nullable: true)]
    private ?string $logo = null;

    /** @var Collection<int, Modele> */
    #[ORM\OneToMany(targetEntity: Modele::class, mappedBy: 'marque')]
    private Collection $modeles;

    public function __construct()
    {
        $this->modeles = new ArrayCollection();
    }

    public function getIdMarque(): ?int
    {
        return $this->idMarque;
    }

    public function getNomMarque(): ?string
    {
        return $this->nomMarque;
    }

    public function setNomMarque(string $nomMarque): static
    {
        $this->nomMarque = $nomMarque;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    /**
     * @return Collection<int, Modele>
     */
    public function getModeles(): Collection
    {
        return $this->modeles;
    }

    public function addModele(Modele $modele): static
    {
        if (!$this->modeles->contains($modele)) {
            $this->modeles->add($modele);
            $modele->setMarque($this);
        }
        return $this;
    }

    public function removeModele(Modele $modele): static
    {
        if ($this->modeles->removeElement($modele)) {
            if ($modele->getMarque() === $this) {
                $modele->setMarque(null);
            }
        }
        return $this;
    }
    public function __toString(): string
{
    return $this->nomMarque ?? '';
}
}