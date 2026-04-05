<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404212551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY location_ibfk_1');
        $this->addSql('ALTER TABLE modele DROP FOREIGN KEY modele_ibfk_1');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY fk_participation_event');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_res_voyage');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY vehicule_ibfk_1');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY fk_voyage_categorie');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE favoris');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE modele');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE password_resets');
        $this->addSql('DROP TABLE publications');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reservationlog');
        $this->addSql('DROP TABLE vehicule');
        $this->addSql('DROP TABLE voyage');
        $this->addSql('ALTER TABLE profil DROP type, DROP statut');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY fk_user_profil');
        $this->addSql('DROP INDEX email ON user');
        $this->addSql('DROP INDEX fk_user_profil ON user');
        $this->addSql('ALTER TABLE user DROP nom, DROP prenom, DROP email, DROP password, DROP telephone, DROP addresse, DROP profil_id, DROP face_descriptor');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commentaire (id INT NOT NULL, publication_id INT NOT NULL, utilisateur_id INT DEFAULT 0, auteur VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, modifie TINYINT(1) DEFAULT 0, INDEX publication_id (publication_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE events (id_event INT AUTO_INCREMENT NOT NULL, titre VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, categorie VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, location VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, prix FLOAT NOT NULL, capacite_max INT NOT NULL, places_restantes INT NOT NULL, image_url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'\'\'\'\'en_attente\'\'\'\'\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, id_createur INT NOT NULL, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY(id_event)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE favoris (utilisateur_id INT NOT NULL, publication_id INT NOT NULL, INDEX publication_id (publication_id), PRIMARY KEY(utilisateur_id, publication_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE location (id_location INT AUTO_INCREMENT NOT NULL, id_vehicule INT NOT NULL, client_nom_complet VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, client_telephone VARCHAR(15) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, client_cin VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, client_adresse VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'Adresse complète du client\', client_ville VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'Ville du client\', client_code_postal VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'Code postal\', client_latitude DOUBLE PRECISION DEFAULT \'NULL\' COMMENT \'Coordonnée GPS - Latitude\', client_longitude DOUBLE PRECISION DEFAULT \'NULL\' COMMENT \'Coordonnée GPS - Longitude\', client_permis_numero VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, date_debut DATETIME NOT NULL, date_fin_prevue DATETIME NOT NULL, date_fin_reelle DATETIME DEFAULT \'NULL\', kilometrage_debut INT UNSIGNED NOT NULL, kilometrage_retour INT UNSIGNED DEFAULT NULL, prix_par_jour NUMERIC(10, 3) NOT NULL COMMENT \'copié du véhicule au moment de la réservation\', montant_total NUMERIC(10, 3) NOT NULL COMMENT \'à calculer : prix × jours + extras\', avance NUMERIC(10, 3) DEFAULT \'0.000\', reste_a_payer NUMERIC(10, 3) DEFAULT \'NULL\', statut ENUM(\'réservée\', \'en_cours\', \'terminée\', \'annulée\', \'no_show\') CHARACTER SET utf8mb4 DEFAULT \'\'\'réservée\'\'\' COLLATE `utf8mb4_general_ci`, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'dégâts, remarques, etc.\', created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_location_coords (client_latitude, client_longitude), INDEX idx_location_dates (date_debut, date_fin_prevue), INDEX idx_location_ville (client_ville), INDEX idx_location_vehicule (id_vehicule), INDEX idx_location_statut (statut), PRIMARY KEY(id_location)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE logement (id INT NOT NULL, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, image VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, adresse VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, capacite INT NOT NULL, equipement VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, tarif_nuit FLOAT NOT NULL, disponibilite TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE marque (id_marque INT AUTO_INCREMENT NOT NULL, nom_marque VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, logo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'URL ou chemin du logo de la marque\', UNIQUE INDEX nom_marque (nom_marque), PRIMARY KEY(id_marque)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_user TINYINT(1) NOT NULL, timestamp DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE modele (id_modele INT AUTO_INCREMENT NOT NULL, id_marque INT NOT NULL, nom_modele VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'URL ou chemin de l\'\'image du modèle\', UNIQUE INDEX unique_modele_par_marque (id_marque, nom_modele), INDEX IDX_100285587C582423 (id_marque), PRIMARY KEY(id_modele)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE participation (id_participation INT AUTO_INCREMENT NOT NULL, id_event INT NOT NULL, nombre_places INT NOT NULL, montant_total FLOAT NOT NULL, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'\'\'\'\'confirmée\'\'\'\'\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, date_participation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX fk_participation_event (id_event), PRIMARY KEY(id_participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE password_resets (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, code VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, expires_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, used TINYINT(1) DEFAULT 0, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_email (email), INDEX idx_code (code), INDEX idx_expires (expires_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE publications (id INT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, categorie VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, utilisateur_id INT DEFAULT 0, auteur VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, likes INT DEFAULT 0, commentaires INT DEFAULT 0, date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, date_reservation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, statut ENUM(\'EN_ATTENTE\', \'CONFIRMEE\', \'ANNULEE\', \'REFUSEE\') CHARACTER SET utf8mb4 DEFAULT \'\'\'EN_ATTENTE\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, id_voyage INT NOT NULL, id_user INT DEFAULT NULL, nbr_personnes INT DEFAULT NULL, INDEX fk_res_voyage (id_voyage), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservationlog (idreslog INT AUTO_INCREMENT NOT NULL, idlog INT NOT NULL, idc INT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, montant FLOAT NOT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, modalites VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, INDEX fk_reservation_logement (idlog), INDEX fk_reservation_user (idc), PRIMARY KEY(idreslog)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vehicule (id_vehicule INT AUTO_INCREMENT NOT NULL, immatriculation VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci` COMMENT \'ex: 123 TUN 45\', id_modele INT NOT NULL, annee INT NOT NULL, carburant ENUM(\'Essence\', \'Diesel\', \'Hybride\', \'Electrique\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, couleur VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, kilometrage INT UNSIGNED DEFAULT 0, etat ENUM(\'disponible\', \'louee\', \'en_maintenance\', \'indisponible\') CHARACTER SET utf8mb4 DEFAULT \'\'\'disponible\'\'\' COLLATE `utf8mb4_general_ci`, prix_par_jour NUMERIC(10, 3) NOT NULL COMMENT \'en TND\', photo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'chemin ou URL\', created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX immatriculation (immatriculation), INDEX id_modele (id_modele), INDEX idx_vehicule_etat (etat), PRIMARY KEY(id_vehicule)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE voyage (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, destination VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, prix DOUBLE PRECISION NOT NULL, date_depart DATE NOT NULL, date_retour DATE NOT NULL, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, id_categorie INT DEFAULT NULL, places_total INT NOT NULL, places_restantes INT NOT NULL, INDEX fk_voyage_categorie (id_categorie), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT location_ibfk_1 FOREIGN KEY (id_vehicule) REFERENCES vehicule (id_vehicule) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE modele ADD CONSTRAINT modele_ibfk_1 FOREIGN KEY (id_marque) REFERENCES marque (id_marque) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT fk_participation_event FOREIGN KEY (id_event) REFERENCES events (id_event) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_res_voyage FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT vehicule_ibfk_1 FOREIGN KEY (id_modele) REFERENCES modele (id_modele) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT fk_voyage_categorie FOREIGN KEY (id_categorie) REFERENCES categorie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE profil ADD type VARCHAR(30) DEFAULT \'NULL\', ADD statut VARCHAR(30) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user ADD nom VARCHAR(100) NOT NULL, ADD prenom VARCHAR(100) NOT NULL, ADD email VARCHAR(255) NOT NULL, ADD password VARCHAR(255) NOT NULL, ADD telephone VARCHAR(20) DEFAULT \'NULL\', ADD addresse VARCHAR(255) DEFAULT \'NULL\', ADD profil_id INT DEFAULT NULL, ADD face_descriptor TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT fk_user_profil FOREIGN KEY (profil_id) REFERENCES profil (id)');
        $this->addSql('CREATE UNIQUE INDEX email ON user (email)');
        $this->addSql('CREATE INDEX fk_user_profil ON user (profil_id)');
    }
}
