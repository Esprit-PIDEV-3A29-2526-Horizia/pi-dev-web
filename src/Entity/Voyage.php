<?php

namespace App\Entity;

use App\Entity\Categorie;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: "voyage")]
class Voyage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 3, minMessage: "Le titre doit contenir au moins 3 caractères.")]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La destination est obligatoire.")]
    private ?string $destination = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, minMessage: "La description doit contenir au moins 10 caractères.")]
    private ?string $description = null;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit être positif.")]
    private ?float $prix = null;

    #[ORM\Column(name: "date_depart", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de départ est obligatoire.")]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(name: "date_retour", type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de retour est obligatoire.")]
    private ?\DateTimeInterface $dateRetour = null;

    #[ORM\Column(name: "image_url", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'voyages')]
    #[ORM\JoinColumn(name: "id_categorie", referencedColumnName: "id", nullable: true)]
    #[Assert\NotNull(message: "La catégorie est obligatoire.")]
    private ?Categorie $categorie = null;

    #[ORM\Column(name: "places_total", type: "integer")]
    #[Assert\NotBlank(message: "Le nombre total de places est obligatoire.")]
    #[Assert\Positive(message: "Le nombre total de places doit être positif.")]
    private ?int $placesTotal = null;

    #[ORM\Column(name: "places_restantes", type: "integer")]
    #[Assert\NotBlank(message: "Le nombre de places restantes est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le nombre de places restantes doit être positif ou nul.")]
    private ?int $placesRestantes = null;

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

    public function setDestination(?string $destination): self
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

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(?\DateTimeInterface $dateDepart): self
    {
        $this->dateDepart = $dateDepart;
        return $this;
    }

    public function getDateRetour(): ?\DateTimeInterface
    {
        return $this->dateRetour;
    }

    public function setDateRetour(?\DateTimeInterface $dateRetour): self
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

    public function setPlacesTotal(?int $placesTotal): self
    {
        $this->placesTotal = $placesTotal;

        if ($placesTotal !== null && $this->placesRestantes === null) {
            $this->placesRestantes = $placesTotal;
        }

        return $this;
    }

    public function getPlacesRestantes(): ?int
    {
        return $this->placesRestantes;
    }

    public function setPlacesRestantes(?int $placesRestantes): self
    {
        $this->placesRestantes = $placesRestantes;
        return $this;
    }

    #[Assert\Callback]
    public function validateDatesAndPlaces(ExecutionContextInterface $context): void
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

        if ($this->placesRestantes !== null && $this->placesRestantes < 0) {
            $context->buildViolation('Les places restantes ne peuvent pas être négatives.')
                ->atPath('placesRestantes')
                ->addViolation();
        }
    }
}