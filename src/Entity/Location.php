<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Location
 *
 * @ORM\Table(name="location", indexes={@ORM\Index(name="idx_location_dates", columns={"date_debut", "date_fin_prevue"}), @ORM\Index(name="idx_location_ville", columns={"client_ville"}), @ORM\Index(name="idx_location_vehicule", columns={"id_vehicule"}), @ORM\Index(name="idx_location_statut", columns={"statut"}), @ORM\Index(name="idx_location_coords", columns={"client_latitude", "client_longitude"})})
 * @ORM\Entity
 */
class Location
{
    /**
     * @var int
     *
     * @ORM\Column(name="id_location", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idLocation;

    /**
     * @var int
     *
     * @ORM\Column(name="id_vehicule", type="integer", nullable=false)
     */
    private $idVehicule;

    /**
     * @var string
     *
     * @ORM\Column(name="client_nom_complet", type="string", length=100, nullable=false)
     */
    private $clientNomComplet;

    /**
     * @var string
     *
     * @ORM\Column(name="client_telephone", type="string", length=15, nullable=false)
     */
    private $clientTelephone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_cin", type="string", length=20, nullable=true)
     */
    private $clientCin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_adresse", type="string", length=255, nullable=true, options={"comment"="Adresse complète du client"})
     */
    private $clientAdresse;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_ville", type="string", length=100, nullable=true, options={"comment"="Ville du client"})
     */
    private $clientVille;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_code_postal", type="string", length=10, nullable=true, options={"comment"="Code postal"})
     */
    private $clientCodePostal;

    /**
     * @var float|null
     *
     * @ORM\Column(name="client_latitude", type="float", precision=10, scale=0, nullable=true, options={"comment"="Coordonnée GPS - Latitude"})
     */
    private $clientLatitude;

    /**
     * @var float|null
     *
     * @ORM\Column(name="client_longitude", type="float", precision=10, scale=0, nullable=true, options={"comment"="Coordonnée GPS - Longitude"})
     */
    private $clientLongitude;

    /**
     * @var string|null
     *
     * @ORM\Column(name="client_permis_numero", type="string", length=20, nullable=true)
     */
    private $clientPermisNumero;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_debut", type="datetime", nullable=false)
     */
    private $dateDebut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_fin_prevue", type="datetime", nullable=false)
     */
    private $dateFinPrevue;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_fin_reelle", type="datetime", nullable=true)
     */
    private $dateFinReelle;

    /**
     * @var int
     *
     * @ORM\Column(name="kilometrage_debut", type="integer", nullable=false, options={"unsigned"=true})
     */
    private $kilometrageDebut;

    /**
     * @var int|null
     *
     * @ORM\Column(name="kilometrage_retour", type="integer", nullable=true, options={"unsigned"=true})
     */
    private $kilometrageRetour;

    /**
     * @var string
     *
     * @ORM\Column(name="prix_par_jour", type="decimal", precision=10, scale=3, nullable=false, options={"comment"="copié du véhicule au moment de la réservation"})
     */
    private $prixParJour;

    /**
     * @var string
     *
     * @ORM\Column(name="montant_total", type="decimal", precision=10, scale=3, nullable=false, options={"comment"="à calculer : prix × jours + extras"})
     */
    private $montantTotal;

    /**
     * @var string|null
     *
     * @ORM\Column(name="avance", type="decimal", precision=10, scale=3, nullable=true, options={"default"="0.000"})
     */
    private $avance = '0.000';

    /**
     * @var string|null
     *
     * @ORM\Column(name="reste_a_payer", type="decimal", precision=10, scale=3, nullable=true)
     */
    private $resteAPayer;

    /**
     * @var string|null
     *
     * @ORM\Column(name="statut", type="string", length=0, nullable=true, options={"default"="réservée"})
     */
    private $statut = 'réservée';

    /**
     * @var string|null
     *
     * @ORM\Column(name="notes", type="text", length=65535, nullable=true, options={"comment"="dégâts, remarques, etc."})
     */
    private $notes;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt = 'CURRENT_TIMESTAMP';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt = 'CURRENT_TIMESTAMP';


}
