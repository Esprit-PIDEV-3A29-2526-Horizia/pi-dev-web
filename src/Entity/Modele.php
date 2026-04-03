<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ModeleRepository;

#[ORM\Entity(repositoryClass: ModeleRepository::class)]
#[ORM\Table(name: 'modele')]
class Modele
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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
    private ?string $nom_modele = null;

    public function getNom_modele(): ?string
    {
        return $this->nom_modele;
    }

    public function setNom_modele(string $nom_modele): self
    {
        $this->nom_modele = $nom_modele;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

}
