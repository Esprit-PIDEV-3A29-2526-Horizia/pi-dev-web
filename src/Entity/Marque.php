<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\MarqueRepository;

#[ORM\Entity(repositoryClass: MarqueRepository::class)]
#[ORM\Table(name: 'marque')]
class Marque
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_marque = null;

    public function getId_marque(): ?int
    {
        return $this->id_marque;
    }

    public function setId_marque(int $id_marque): self
    {
        $this->id_marque = $id_marque;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_marque = null;

    public function getNom_marque(): ?string
    {
        return $this->nom_marque;
    }

    public function setNom_marque(string $nom_marque): self
    {
        $this->nom_marque = $nom_marque;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $logo = null;

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

}
