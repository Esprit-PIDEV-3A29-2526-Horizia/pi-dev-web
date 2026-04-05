<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405105805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservationlog DROP FOREIGN KEY fk_reservation_logement');
        $this->addSql('DROP TABLE logement');
        $this->addSql('ALTER TABLE categorie CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY commentaire_ibfk_1');
        $this->addSql('ALTER TABLE commentaire CHANGE publication_id publication_id INT DEFAULT NULL, CHANGE utilisateur_id utilisateur_id INT DEFAULT NULL, CHANGE modifie modifie TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publications (id)');
        $this->addSql('ALTER TABLE events CHANGE id_event id_event INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE location CHANGE id_location id_location INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE marque CHANGE id_marque id_marque INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE messages CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE modele CHANGE id_modele id_modele INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE participation CHANGE id_participation id_participation INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE password_resets CHANGE used used TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE publications CHANGE utilisateur_id utilisateur_id INT DEFAULT NULL, CHANGE likes likes INT DEFAULT NULL, CHANGE commentaires commentaires INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD prix_total DOUBLE PRECISION DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495519AA3CB8 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849556B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX fk_res_user ON reservation (id_user)');
        $this->addSql('ALTER TABLE reservationlog DROP FOREIGN KEY fk_reservation_user');
        $this->addSql('ALTER TABLE vehicule CHANGE id_vehicule id_vehicule INT AUTO_INCREMENT NOT NULL, CHANGE kilometrage kilometrage INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE voyage CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D8955C9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE logement (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, nom VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, adresse VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, capacite INT NOT NULL, equipement VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, tarif_nuit DOUBLE PRECISION NOT NULL, disponibilite TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE categorie CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire CHANGE publication_id publication_id INT NOT NULL, CHANGE utilisateur_id utilisateur_id INT DEFAULT 0, CHANGE modifie modifie TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT commentaire_ibfk_1 FOREIGN KEY (publication_id) REFERENCES publications (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events CHANGE id_event id_event INT NOT NULL');
        $this->addSql('ALTER TABLE location CHANGE id_location id_location INT NOT NULL');
        $this->addSql('ALTER TABLE marque CHANGE id_marque id_marque INT NOT NULL');
        $this->addSql('ALTER TABLE messages CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE modele CHANGE id_modele id_modele INT NOT NULL');
        $this->addSql('ALTER TABLE participation CHANGE id_participation id_participation INT NOT NULL');
        $this->addSql('ALTER TABLE password_resets CHANGE used used TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE publications CHANGE utilisateur_id utilisateur_id INT DEFAULT 0, CHANGE likes likes INT DEFAULT 0, CHANGE commentaires commentaires INT DEFAULT 0');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495519AA3CB8');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556B3CA4B');
        $this->addSql('DROP INDEX fk_res_user ON reservation');
        $this->addSql('ALTER TABLE reservation DROP prix_total, CHANGE statut statut VARCHAR(255) DEFAULT \'EN_ATTENTE\' NOT NULL');
        $this->addSql('ALTER TABLE reservationlog ADD CONSTRAINT fk_reservation_user FOREIGN KEY (idc) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservationlog ADD CONSTRAINT fk_reservation_logement FOREIGN KEY (idlog) REFERENCES logement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicule CHANGE id_vehicule id_vehicule INT NOT NULL, CHANGE kilometrage kilometrage INT UNSIGNED DEFAULT 0');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D8955C9486A13');
        $this->addSql('ALTER TABLE voyage CHANGE id id INT NOT NULL');
    }
}
