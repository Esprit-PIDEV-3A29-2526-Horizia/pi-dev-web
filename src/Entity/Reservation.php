<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reservation
 *
 * @ORM\Table(name="reservation", indexes={
 *     @ORM\Index(name="fk_res_voyage", columns={"id_voyage"}),
 *     @ORM\Index(name="fk_res_user", columns={"id_user"})
 * })
 * @ORM\Entity
 */
class Reservation
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(name="date_reservation", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     * @Assert\NotBlank(message="La date de réservation est obligatoire.")
     */
    private $dateReservation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="statut", type="string", length=50, nullable=false, options={"default"="EN_ATTENTE"})
     * @Assert\NotBlank(message="Le statut est obligatoire.")
     */
    private $statut = 'EN_ATTENTE';

    /**
     * @var Voyage|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Voyage")
     * @ORM\JoinColumn(name="id_voyage", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull(message="Le voyage est obligatoire.")
     */
    private $voyage;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $user;

    /**
     * @var int|null
     *
     * @ORM\Column(name="nbr_personnes", type="integer", nullable=true)
     * @Assert\NotBlank(message="Le nombre de personnes est obligatoire.")
     * @Assert\Positive(message="Le nombre de personnes doit être positif.")
     */
    private $nbrPersonnes;

    /**
     * @var float|null
     *
     * @ORM\Column(name="prix_total", type="float", nullable=true)
     */
    private $prixTotal;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): self
    {
        $this->voyage = $voyage;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getNbrPersonnes(): ?int
    {
        return $this->nbrPersonnes;
    }

    public function setNbrPersonnes(?int $nbrPersonnes): self
    {
        $this->nbrPersonnes = $nbrPersonnes;
        return $this;
    }

    public function getPrixTotal(): ?float
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(?float $prixTotal): self
    {
        $this->prixTotal = $prixTotal;
        return $this;
    }
}