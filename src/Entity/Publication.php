<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: "publication")]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: "La catégorie est obligatoire.")]
    private ?string $categorie = null;

    #[ORM\Column(nullable: true)]
    private ?int $utilisateur_id = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $auteur = null;

    #[ORM\Column(nullable: true)]
    private ?int $likes = 0;

    #[ORM\Column(nullable: true)]
    private ?int $commentaires = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_creation = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pays = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tags = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication', cascade: ['remove'], orphanRemoval: true)]
    private Collection $commentaireList;

    public function __construct()
    {
        $this->date_creation = new \DateTimeImmutable();
        $this->commentaireList = new ArrayCollection();
    }

    // Id
    public function getId(): ?int
    {
        return $this->id;
    }

    // Titre
    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    // Description
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    // Image
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    // Catégorie
    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    // Utilisateur_id
    public function getUtilisateurId(): ?int
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateurId(?int $utilisateur_id): static
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    // Auteur
    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;
        return $this;
    }

    // Likes
    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(?int $likes): static
    {
        $this->likes = $likes;
        return $this;
    }

    // Commentaires (compteur)
    public function getCommentaires(): ?int
    {
        return $this->commentaires;
    }

    public function setCommentaires(?int $commentaires): static
    {
        $this->commentaires = $commentaires;
        return $this;
    }

    // Date de création
    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeImmutable $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    // Ville
    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    // Pays
    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;
        return $this;
    }

    // Tags
    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    // Collection de commentaires
    public function getCommentaireList(): Collection
    {
        return $this->commentaireList;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaireList->contains($commentaire)) {
            $this->commentaireList->add($commentaire);
            $commentaire->setPublication($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaireList->removeElement($commentaire)) {
            if ($commentaire->getPublication() === $this) {
                $commentaire->setPublication(null);
            }
        }
        return $this;
    }

    public function addCommentaireList(Commentaire $commentaireList): static
    {
        if (!$this->commentaireList->contains($commentaireList)) {
            $this->commentaireList->add($commentaireList);
            $commentaireList->setPublication($this);
        }

        return $this;
    }

    public function removeCommentaireList(Commentaire $commentaireList): static
    {
        if ($this->commentaireList->removeElement($commentaireList)) {
            // set the owning side to null (unless already changed)
            if ($commentaireList->getPublication() === $this) {
                $commentaireList->setPublication(null);
            }
        }

        return $this;
    }
}