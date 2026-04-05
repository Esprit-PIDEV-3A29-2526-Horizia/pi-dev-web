<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Marque
 *
 * @ORM\Table(name="marque", uniqueConstraints={@ORM\UniqueConstraint(name="nom_marque", columns={"nom_marque"})})
 * @ORM\Entity
 */
class Marque
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_marque", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idMarque;

    /**
     * @var string
     *
     * @ORM\Column(name="nom_marque", type="string", length=50, nullable=false)
     */
    private $nomMarque;

    /**
     * @var string|null
     *
     * @ORM\Column(name="logo", type="string", length=255, nullable=true, options={"comment"="URL ou chemin du logo de la marque"})
     */
    private $logo;

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


}
