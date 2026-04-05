<?php

namespace App\Entity;

<<<<<<< HEAD
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $addresse = null;

    #[ORM\ManyToOne(targetEntity: Profil::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'profil_id', referencedColumnName: 'id')]
    private ?Profil $profil = null;

    // ===== GETTERS & SETTERS =====
=======
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email"})}, indexes={@ORM\Index(name="fk_user_profil", columns={"profil_id"})})
 * @ORM\Entity
 */
class User
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
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=100, nullable=false)
     * @Assert\NotBlank(message="Le nom est obligatoire.")
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=100, nullable=false)
     * @Assert\NotBlank(message="Le prénom est obligatoire.")
     */
    private $prenom;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     * @Assert\NotBlank(message="L'email est obligatoire.")
     * @Assert\Email(message="Veuillez saisir un email valide.")
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     * @Assert\NotBlank(message="Le mot de passe est obligatoire.")
     * @Assert\Length(min=6, minMessage="Le mot de passe doit contenir au moins 6 caractères.")
     */
    private $password;

    /**
     * @var string|null
     *
     * @ORM\Column(name="telephone", type="string", length=20, nullable=true)
     */
    private $telephone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addresse", type="string", length=255, nullable=true)
     */
    private $addresse;

    /**
     * @var string|null
     *
     * @ORM\Column(name="face_descriptor", type="text", length=65535, nullable=true)
     */
    private $faceDescriptor;

    /**
     * @var \App\Entity\Profil|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Profil")
     * @ORM\JoinColumn(name="profil_id", referencedColumnName="id", nullable=true)
     */
    private $profil;
>>>>>>> origin/gestion_voyage

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }
<<<<<<< HEAD
    
=======

>>>>>>> origin/gestion_voyage
    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAddresse(): ?string
    {
        return $this->addresse;
    }

    public function setAddresse(?string $addresse): self
    {
        $this->addresse = $addresse;
        return $this;
    }

<<<<<<< HEAD
=======
    public function getFaceDescriptor(): ?string
    {
        return $this->faceDescriptor;
    }

    public function setFaceDescriptor(?string $faceDescriptor): self
    {
        $this->faceDescriptor = $faceDescriptor;
        return $this;
    }

>>>>>>> origin/gestion_voyage
    public function getProfil(): ?Profil
    {
        return $this->profil;
    }

    public function setProfil(?Profil $profil): self
    {
        $this->profil = $profil;
        return $this;
    }
<<<<<<< HEAD

    // ===== MÉTHODES REQUISES PAR UserInterface =====

    public function getRoles(): array
    {
        // Si l'utilisateur a le profil ADMIN
        if ($this->profil && $this->profil->getType() === 'ADMIN') {
            return ['ROLE_ADMIN', 'ROLE_USER'];
        }
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Efface les informations sensibles temporaires
    }
=======
>>>>>>> origin/gestion_voyage
}