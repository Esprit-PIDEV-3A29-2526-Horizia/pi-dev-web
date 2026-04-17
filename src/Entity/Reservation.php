<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reservation
 *
 * @ORM\Table(name="reservation")
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
     * @ORM\Column(name="date_reservation", type="datetime", nullable=false)
     */
    private $dateReservation;

    /**
     * @var string|null
     *
     * @ORM\Column(name="statut", type="string", length=50, nullable=false)
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
     * @ORM\Column(name="nb_adultes", type="integer", nullable=false)
     * @Assert\NotBlank(message="Le nombre d'adultes est obligatoire.")
     * @Assert\PositiveOrZero(message="Le nombre d'adultes ne peut pas être négatif.")
     */
    private $nbAdultes = 1;

    /**
     * @var int|null
     *
     * @ORM\Column(name="nb_enfants", type="integer", nullable=false)
     * @Assert\NotBlank(message="Le nombre d'enfants est obligatoire.")
     * @Assert\PositiveOrZero(message="Le nombre d'enfants ne peut pas être négatif.")
     */
    private $nbEnfants = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(name="nbr_personnes", type="integer", nullable=false)
     * @Assert\NotBlank(message="Le nombre total de personnes est obligatoire.")
     * @Assert\Positive(message="Le nombre total de personnes doit être supérieur à 0.")
     */
    private $nbrPersonnes = 1;

        /**
     * @var float|null
     *
     * @ORM\Column(name="prix_total", type="float", nullable=true)
     */
    private $prixTotal;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
        $this->recalculerNbrPersonnes();
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
        $this->recalculerPrixTotal();
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

    public function getNbAdultes(): ?int
    {
        return $this->nbAdultes;
    }

    public function setNbAdultes(?int $nbAdultes): self
    {
        $this->nbAdultes = $nbAdultes ?? 0;
        $this->recalculerNbrPersonnes();

        return $this;
    }

    public function getNbEnfants(): ?int
    {
        return $this->nbEnfants;
    }

    public function setNbEnfants(?int $nbEnfants): self
    {
        $this->nbEnfants = $nbEnfants ?? 0;
        $this->recalculerNbrPersonnes();

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

        public function recalculerNbrPersonnes(): void
    {
        $adultes = $this->nbAdultes ?? 0;
        $enfants = $this->nbEnfants ?? 0;
        $this->nbrPersonnes = $adultes + $enfants;
        $this->recalculerPrixTotal();
    }

    /**
     * @Assert\Callback
     */
    public function validateReservation(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if (($this->nbAdultes ?? 0) + ($this->nbEnfants ?? 0) <= 0) {
            $context->buildViolation('La réservation doit contenir au moins 1 personne.')
                ->atPath('nbAdultes')
                ->addViolation();
        }

        if ($this->voyage && $this->nbrPersonnes > $this->voyage->getPlacesRestantes()) {
            $context->buildViolation('Le nombre demandé dépasse les places restantes.')
                ->atPath('nbAdultes')
                ->addViolation();
        }
    }

        public function recalculerPrixTotal(): void
    {
        if ($this->voyage) {
            $this->prixTotal = ($this->voyage->getPrix() ?? 0) * ($this->nbrPersonnes ?? 0);
        } else {
            $this->prixTotal = 0;
        }
    }
}