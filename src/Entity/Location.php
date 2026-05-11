<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'location')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_location', type: 'integer')]
    private ?int $idLocation = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(name: 'id_vehicule', referencedColumnName: 'id_vehicule', nullable: false)]
    private ?Vehicule $vehicule = null;

    // ✅ NOUVEAU : Relation avec l'utilisateur connecté
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    #[ORM\Column(name: 'client_nom_complet', type: 'string', length: 100)]
    private ?string $clientNomComplet = null;

    #[ORM\Column(name: 'client_telephone', type: 'string', length: 15)]
    private ?string $clientTelephone = null;

    #[ORM\Column(name: 'client_cin', type: 'string', length: 20, nullable: true)]
    private ?string $clientCin = null;

    #[ORM\Column(name: 'client_adresse', type: 'string', length: 255, nullable: true)]
    private ?string $clientAdresse = null;

    #[ORM\Column(name: 'client_ville', type: 'string', length: 100, nullable: true)]
    private ?string $clientVille = null;

    #[ORM\Column(name: 'client_code_postal', type: 'string', length: 10, nullable: true)]
    private ?string $clientCodePostal = null;

    #[ORM\Column(name: 'client_latitude', type: 'float', nullable: true)]
    private ?float $clientLatitude = null;

    #[ORM\Column(name: 'client_longitude', type: 'float', nullable: true)]
    private ?float $clientLongitude = null;

    #[ORM\Column(name: 'client_permis_numero', type: 'string', length: 20, nullable: true)]
    private ?string $clientPermisNumero = null;

    #[ORM\Column(name: 'date_debut', type: 'datetime')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin_prevue', type: 'datetime')]
    private ?\DateTimeInterface $dateFinPrevue = null;

    #[ORM\Column(name: 'date_fin_reelle', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateFinReelle = null;

    #[ORM\Column(name: 'kilometrage_debut', type: 'integer')]
    private ?int $kilometrageDebut = null;

    #[ORM\Column(name: 'kilometrage_retour', type: 'integer', nullable: true)]
    private ?int $kilometrageRetour = null;

    #[ORM\Column(name: 'prix_par_jour', type: 'decimal', precision: 10, scale: 3)]
    private ?string $prixParJour = null;

    #[ORM\Column(name: 'montant_total', type: 'decimal', precision: 10, scale: 3)]
    private ?string $montantTotal = null;

    #[ORM\Column(name: 'avance', type: 'decimal', precision: 10, scale: 3, nullable: true)]
    private ?string $avance = null;

    #[ORM\Column(name: 'statut', type: 'string', length: 20, nullable: true)]
    private ?string $statut = 'réservée';

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    // Champ extras JSON
    /** @var array<string, float|int|string>|null */
    #[ORM\Column(name: 'extras', type: 'json', nullable: true)]
    private ?array $extras = null;

    // ──────────────────────────────────────────
    // GETTERS / SETTERS
    // ──────────────────────────────────────────

    public function getIdLocation(): ?int
    {
        return $this->idLocation;
    }

    public function getVehicule(): ?Vehicule
    {
        return $this->vehicule;
    }

    public function setVehicule(?Vehicule $vehicule): static
    {
        $this->vehicule = $vehicule;
        return $this;
    }

    // ✅ NOUVEAU : Getter/Setter pour User
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getClientNomComplet(): ?string
    {
        return $this->clientNomComplet;
    }

    public function setClientNomComplet(string $clientNomComplet): static
    {
        $this->clientNomComplet = $clientNomComplet;
        return $this;
    }

    public function getClientTelephone(): ?string
    {
        return $this->clientTelephone;
    }

    public function setClientTelephone(string $clientTelephone): static
    {
        $this->clientTelephone = $clientTelephone;
        return $this;
    }

    public function getClientCin(): ?string
    {
        return $this->clientCin;
    }

    public function setClientCin(?string $clientCin): static
    {
        $this->clientCin = $clientCin;
        return $this;
    }

    public function getClientAdresse(): ?string
    {
        return $this->clientAdresse;
    }

    public function setClientAdresse(?string $clientAdresse): static
    {
        $this->clientAdresse = $clientAdresse;
        return $this;
    }

    public function getClientVille(): ?string
    {
        return $this->clientVille;
    }

    public function setClientVille(?string $clientVille): static
    {
        $this->clientVille = $clientVille;
        return $this;
    }

    public function getClientCodePostal(): ?string
    {
        return $this->clientCodePostal;
    }

    public function setClientCodePostal(?string $clientCodePostal): static
    {
        $this->clientCodePostal = $clientCodePostal;
        return $this;
    }

    public function getClientLatitude(): ?float
    {
        return $this->clientLatitude;
    }

    public function setClientLatitude(?float $clientLatitude): static
    {
        $this->clientLatitude = $clientLatitude;
        return $this;
    }

    public function getClientLongitude(): ?float
    {
        return $this->clientLongitude;
    }

    public function setClientLongitude(?float $clientLongitude): static
    {
        $this->clientLongitude = $clientLongitude;
        return $this;
    }

    public function getClientPermisNumero(): ?string
    {
        return $this->clientPermisNumero;
    }

    public function setClientPermisNumero(?string $clientPermisNumero): static
    {
        $this->clientPermisNumero = $clientPermisNumero;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFinPrevue(): ?\DateTimeInterface
    {
        return $this->dateFinPrevue;
    }

    public function setDateFinPrevue(\DateTimeInterface $dateFinPrevue): static
    {
        $this->dateFinPrevue = $dateFinPrevue;
        return $this;
    }

    public function getDateFinReelle(): ?\DateTimeInterface
    {
        return $this->dateFinReelle;
    }

    public function setDateFinReelle(?\DateTimeInterface $dateFinReelle): static
    {
        $this->dateFinReelle = $dateFinReelle;
        return $this;
    }

    public function getKilometrageDebut(): ?int
    {
        return $this->kilometrageDebut;
    }

    public function setKilometrageDebut(int $kilometrageDebut): static
    {
        $this->kilometrageDebut = $kilometrageDebut;
        return $this;
    }

    public function getKilometrageRetour(): ?int
    {
        return $this->kilometrageRetour;
    }

    public function setKilometrageRetour(?int $kilometrageRetour): static
    {
        $this->kilometrageRetour = $kilometrageRetour;
        return $this;
    }

    public function getPrixParJour(): ?string
    {
        return $this->prixParJour;
    }

    public function setPrixParJour(string $prixParJour): static
    {
        $this->prixParJour = $prixParJour;
        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;
        return $this;
    }

    public function getAvance(): ?string
    {
        return $this->avance;
    }

    public function setAvance(?string $avance): static
    {
        $this->avance = $avance;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    // ──────────────────────────────────────────
    // EXTRAS
    // ──────────────────────────────────────────

    /** @return array<string, float|int|string>|null */

    public function getExtras(): ?array
    {
        return $this->extras;
    }

    /** @param array<string, float|int|string>|null $extras */
    public function setExtras(?array $extras): static
    {
        $this->extras = $extras;
        return $this;
    }

    /**
     * Retourne le total des extras en TND
     */
    public function getExtrasTotal(): float
    {
        if (!$this->extras) {
            return 0.0;
        }
        return (float) array_sum($this->extras);
    }

    /**
     * Calcule et met à jour le montant total automatiquement
     * Formule : prix_par_jour × nb_jours + total_extras
     */
    public function calculerMontantTotal(): void
    {
        $debut = $this->dateDebut;
        $fin   = $this->dateFinPrevue;
        if (!$debut || !$fin) {
            return;
        }
        $jours = max(1, (int) $debut->diff($fin)->days);
        $base = (float) $this->prixParJour * $jours;
        $this->montantTotal = (string) round($base + $this->getExtrasTotal(), 3);
    }

    /**
     * Calcule le reste à payer (montantTotal - avance)
     */
    public function getResteAPayer(): float
    {
        return max(0, (float) $this->montantTotal - (float) $this->avance);
    }

    /**
     * Remplit automatiquement les informations client à partir de l'utilisateur
     */
    public function fillFromUser(User $user): void
    {
        $this->setUser($user);
        $this->setClientNomComplet($user->getNom() . ' ' . $user->getPrenom());
        $this->setClientTelephone($user->getTelephone() ?? '');
        $this->setClientAdresse($user->getAddresse() ?? '');
    }
}