<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406003535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE favoris');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE modele');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE publications');
        $this->addSql('DROP TABLE reservationlog');
        $this->addSql('DROP TABLE vehicule');
        $this->addSql('ALTER TABLE categorie CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE image_url image_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE password_resets CHANGE code code VARCHAR(255) NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE used used TINYINT(1) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE profil CHANGE type type VARCHAR(30) DEFAULT NULL, CHANGE statut statut VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD prix_total DOUBLE PRECISION DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE date_reservation date_reservation DATETIME NOT NULL, CHANGE statut statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495519AA3CB8 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849556B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_42C8495519AA3CB8 ON reservation (id_voyage)');
        $this->addSql('CREATE INDEX IDX_42C849556B3CA4B ON reservation (id_user)');
        $this->addSql('ALTER TABLE user ADD face_descriptor LONGTEXT DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE addresse addresse VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('ALTER TABLE user RENAME INDEX fk_user_profil TO IDX_8D93D649275ED078');
        $this->addSql('ALTER TABLE voyage CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE image_url image_url VARCHAR(255) DEFAULT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D8955C9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id)');
        $this->addSql('CREATE INDEX IDX_3F9D8955C9486A13 ON voyage (id_categorie)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire (id INT NOT NULL, publication_id INT NOT NULL, utilisateur_id INT DEFAULT 0, auteur VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, modifie TINYINT(1) DEFAULT 0, INDEX publication_id (publication_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE events (id_event INT NOT NULL, titre VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, categorie VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, location VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, prix FLOAT NOT NULL, capacite_max INT NOT NULL, places_restantes INT NOT NULL, image_url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'\'\'\'\'en_attente\'\'\'\'\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, id_createur INT NOT NULL, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY(id_event)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE favoris (utilisateur_id INT NOT NULL, publication_id INT NOT NULL, INDEX publication_id (publication_id), PRIMARY KEY(utilisateur_id, publication_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE location (id_location INT NOT NULL, id_vehicule INT NOT NULL, client_nom_complet VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, client_telephone VARCHAR(15) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, client_cin VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, client_adresse VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'Adresse complète du client\', client_ville VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'Ville du client\', client_code_postal VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'Code postal\', client_latitude DOUBLE PRECISION DEFAULT \'NULL\' COMMENT \'Coordonnée GPS - Latitude\', client_longitude DOUBLE PRECISION DEFAULT \'NULL\' COMMENT \'Coordonnée GPS - Longitude\', client_permis_numero VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, date_debut DATETIME NOT NULL, date_fin_prevue DATETIME NOT NULL, date_fin_reelle DATETIME DEFAULT \'NULL\', kilometrage_debut INT UNSIGNED NOT NULL, kilometrage_retour INT UNSIGNED DEFAULT NULL, prix_par_jour NUMERIC(10, 3) NOT NULL COMMENT \'copié du véhicule au moment de la réservation\', montant_total NUMERIC(10, 3) NOT NULL COMMENT \'à calculer : prix × jours + extras\', avance NUMERIC(10, 3) DEFAULT \'0.000\', reste_a_payer NUMERIC(10, 3) DEFAULT \'NULL\', statut ENUM(\'réservée\', \'en_cours\', \'terminée\', \'annulée\', \'no_show\') CHARACTER SET utf8mb4 DEFAULT \'\'\'réservée\'\'\' COLLATE `utf8mb4_general_ci`, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci` COMMENT \'dégâts, remarques, etc.\', created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, INDEX idx_location_coords (client_latitude, client_longitude), INDEX idx_location_dates (date_debut, date_fin_prevue), INDEX idx_location_ville (client_ville), INDEX idx_location_vehicule (id_vehicule), INDEX idx_location_statut (statut), PRIMARY KEY(id_location)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE logement (id INT NOT NULL, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, image VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, adresse VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, capacite INT NOT NULL, equipement VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, tarif_nuit FLOAT NOT NULL, disponibilite TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE marque (id_marque INT NOT NULL, nom_marque VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, logo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'URL ou chemin du logo de la marque\', UNIQUE INDEX nom_marque (nom_marque), PRIMARY KEY(id_marque)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE messages (id INT NOT NULL, user_id INT NOT NULL, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_user TINYINT(1) NOT NULL, timestamp DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE modele (id_modele INT NOT NULL, id_marque INT NOT NULL, nom_modele VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'URL ou chemin de l\'\'image du modèle\') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE participation (id_participation INT NOT NULL, id_event INT NOT NULL, nombre_places INT NOT NULL, montant_total FLOAT NOT NULL, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'\'\'\'\'confirmée\'\'\'\'\'\'\' NOT NULL COLLATE `utf8mb4_general_ci`, date_participation DATETIME DEFAULT \'current_timestamp()\' NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE publications (id INT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, categorie VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, utilisateur_id INT DEFAULT 0, auteur VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, likes INT DEFAULT 0, commentaires INT DEFAULT 0, date_creation DATETIME DEFAULT \'current_timestamp()\' NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservationlog (idreslog INT NOT NULL, idlog INT NOT NULL, idc INT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, montant FLOAT NOT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, modalites VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vehicule (id_vehicule INT NOT NULL, immatriculation VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci` COMMENT \'ex: 123 TUN 45\', id_modele INT NOT NULL, annee INT NOT NULL, carburant ENUM(\'Essence\', \'Diesel\', \'Hybride\', \'Electrique\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, couleur VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, kilometrage INT UNSIGNED DEFAULT 0, etat ENUM(\'disponible\', \'louee\', \'en_maintenance\', \'indisponible\') CHARACTER SET utf8mb4 DEFAULT \'\'\'disponible\'\'\' COLLATE `utf8mb4_general_ci`, prix_par_jour NUMERIC(10, 3) NOT NULL COMMENT \'en TND\', photo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci` COMMENT \'chemin ou URL\', created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE categorie CHANGE id id INT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE image_url image_url VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE password_resets CHANGE code code VARCHAR(10) NOT NULL, CHANGE expires_at expires_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE used used TINYINT(1) DEFAULT 0, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE profil CHANGE type type VARCHAR(30) DEFAULT \'NULL\', CHANGE statut statut VARCHAR(30) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE reservation MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495519AA3CB8');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556B3CA4B');
        $this->addSql('DROP INDEX IDX_42C8495519AA3CB8 ON reservation');
        $this->addSql('DROP INDEX IDX_42C849556B3CA4B ON reservation');
        $this->addSql('DROP INDEX `primary` ON reservation');
        $this->addSql('ALTER TABLE reservation DROP prix_total, CHANGE id id INT NOT NULL, CHANGE date_reservation date_reservation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE statut statut ENUM(\'EN_ATTENTE\', \'CONFIRMEE\', \'ANNULEE\', \'REFUSEE\') DEFAULT \'\'\'EN_ATTENTE\'\'\' NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('ALTER TABLE user DROP face_descriptor, CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE addresse addresse VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user RENAME INDEX idx_8d93d649275ed078 TO fk_user_profil');
        $this->addSql('ALTER TABLE voyage MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D8955C9486A13');
        $this->addSql('DROP INDEX IDX_3F9D8955C9486A13 ON voyage');
        $this->addSql('DROP INDEX `primary` ON voyage');
        $this->addSql('ALTER TABLE voyage CHANGE id id INT NOT NULL, CHANGE titre titre VARCHAR(255) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL, CHANGE image_url image_url VARCHAR(255) DEFAULT \'NULL\'');
    }
}
