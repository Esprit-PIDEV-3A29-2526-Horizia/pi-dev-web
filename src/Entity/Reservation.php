<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reservation
 *
 * @ORM\Table(name="reservation", indexes={@ORM\Index(name="fk_res_voyage", columns={"id_voyage"})})
 * @ORM\Entity
 */
class Reservation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="date_reservation", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     * @Assert\NotBlank(message="La date de réservation est obligatoire.")
     */
    private $dateReservation;

    /**
     * @var string
     *
     * @ORM\Column(name="statut", type="string", length=50, nullable=false, options={"default"="EN_ATTENTE"})
     * @Assert\NotBlank(message="Le statut est obligatoire.")
     */
    private $statut = 'EN_ATTENTE';

    /**
     * @var int
     *
     * @ORM\Column(name="id_voyage", type="integer", nullable=false)
     * @Assert\NotBlank(message="L'ID du voyage est obligatoire.")
     * @Assert\Positive(message="L'ID du voyage doit être positif.")
     */
    private $idVoyage;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id_user", type="integer", nullable=true)
     * @Assert\Positive(message="L'ID utilisateur doit être positif.")
     */
    private $idUser;

    /**
     * @var int|null
     *
     * @ORM\Column(name="nbr_personnes", type="integer", nullable=true)
     * @Assert\NotBlank(message="Le nombre de personnes est obligatoire.")
     * @Assert\Positive(message="Le nombre de personnes doit être positif.")
     */
    private $nbrPersonnes;

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

    public function getIdVoyage(): ?int
    {
        return $this->idVoyage;
    }

    public function setIdVoyage(int $idVoyage): self
    {
        $this->idVoyage = $idVoyage;
        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function setIdUser(?int $idUser): self
    {
        $this->idUser = $idUser;
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
}