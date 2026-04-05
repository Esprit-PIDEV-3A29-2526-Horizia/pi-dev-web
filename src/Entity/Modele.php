<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Modele
 *
 * @ORM\Table(name="modele", uniqueConstraints={@ORM\UniqueConstraint(name="unique_modele_par_marque", columns={"id_marque", "nom_modele"})})
 * @ORM\Entity
 */
class Modele
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_modele", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idModele;

    /**
     * @var int
     *
     * @ORM\Column(name="id_marque", type="integer", nullable=false)
     */
    private $idMarque;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_modele", type="string", length=80, nullable=false)
     */
    private $nomModele;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true, options={"comment"="URL ou chemin de l'image du modèle"})
     */
    private $image;

    public function getIdModele(): ?int
    {
        return $this->idModele;
    }

    public function getIdMarque(): ?int
    {
        return $this->idMarque;
    }

    public function setIdMarque(int $idMarque): static
    {
        $this->idMarque = $idMarque;

        return $this;
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


}
