<?php

namespace App\Entity;
use App\Entity\Categorie;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Voyage
 *
 * @ORM\Table(name="voyage", indexes={@ORM\Index(name="fk_voyage_categorie", columns={"id_categorie"})})
 * @ORM\Entity
 */
class Voyage
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
     * @var string|null
     *
     * @ORM\Column(name="titre", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Le titre est obligatoire.")
     * @Assert\Length(min=3, minMessage="Le titre doit contenir au moins 3 caractères.")
     */
    private $titre;

    /**
     * @var string
     *
     * @ORM\Column(name="destination", type="string", length=255, nullable=false)
     * @Assert\NotBlank(message="La destination est obligatoire.")
     */
    private $destination;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     * @Assert\NotBlank(message="La description est obligatoire.")
     * @Assert\Length(min=10, minMessage="La description doit contenir au moins 10 caractères.")
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="prix", type="float", precision=10, scale=0, nullable=false)
     * @Assert\NotBlank(message="Le prix est obligatoire.")
     * @Assert\Positive(message="Le prix doit être positif.")
     */
    private $prix;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="date_depart", type="date", nullable=false)
     * @Assert\NotBlank(message="La date de départ est obligatoire.")
     */
    private $dateDepart;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="date_retour", type="date", nullable=false)
     * @Assert\NotBlank(message="La date de retour est obligatoire.")
     */
    private $dateRetour;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image_url", type="string", length=255, nullable=true)
     */
    private $imageUrl;

    /**
    * @var Categorie|null
    *
    * @ORM\ManyToOne(targetEntity="App\Entity\Categorie", inversedBy="voyages")
    * @ORM\JoinColumn(name="id_categorie", referencedColumnName="id", nullable=true)
    * @Assert\NotNull(message="La catégorie est obligatoire.")
    */
    private $categorie;

    /**
     * @var int
     *
     * @ORM\Column(name="places_total", type="integer", nullable=false)
     * @Assert\NotBlank(message="Le nombre total de places est obligatoire.")
     * @Assert\Positive(message="Le nombre total de places doit être positif.")
     */
    private $placesTotal;

    /**
     * @var int
     *
     * @ORM\Column(name="places_restantes", type="integer", nullable=false)
     * @Assert\NotBlank(message="Le nombre de places restantes est obligatoire.")
     * @Assert\PositiveOrZero(message="Le nombre de places restantes doit être positif ou nul.")
     */
    private $placesRestantes;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getDateRetour(): ?\DateTimeInterface
    {
        return $this->dateRetour;
    }

    public function setDateRetour(\DateTimeInterface $dateRetour): self
    {
        $this->dateRetour = $dateRetour;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }
    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getPlacesTotal(): ?int
    {
        return $this->placesTotal;
    }

    public function setPlacesTotal(int $placesTotal): self
    {
        $this->placesTotal = $placesTotal;
        return $this;
    }

    public function getPlacesRestantes(): ?int
    {
        return $this->placesRestantes;
    }

    public function setPlacesRestantes(int $placesRestantes): self
    {
        $this->placesRestantes = $placesRestantes;
        return $this;
    }

    #[Assert\Callback]
    public function validateDatesAndPlaces(\Symfony\Component\Validator\Context\ExecutionContextInterface $context): void
    {
        if ($this->dateDepart && $this->dateRetour && $this->dateRetour < $this->dateDepart) {
            $context->buildViolation('La date de retour doit être postérieure à la date de départ.')
            ->atPath('dateRetour')
            ->addViolation();
        }

        if ($this->placesRestantes !== null && $this->placesTotal !== null && $this->placesRestantes > $this->placesTotal) {
            $context->buildViolation('Les places restantes ne peuvent pas dépasser les places totales.')
            ->atPath('placesRestantes')
            ->addViolation();
        }
    }
}