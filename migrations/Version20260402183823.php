<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402183823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE categorie ADD image_url VARCHAR(255) DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
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
        $this->addSql('ALTER TABLE reservation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE statut statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL');
        $this->addSql('ALTER TABLE vehicule CHANGE id_vehicule id_vehicule INT AUTO_INCREMENT NOT NULL, CHANGE kilometrage kilometrage INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D8955C9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE categorie DROP image_url, CHANGE id id INT NOT NULL');
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
        $this->addSql('ALTER TABLE reservation CHANGE id id INT NOT NULL, CHANGE statut statut VARCHAR(255) DEFAULT \'EN_ATTENTE\' NOT NULL');
        $this->addSql('ALTER TABLE vehicule CHANGE id_vehicule id_vehicule INT NOT NULL, CHANGE kilometrage kilometrage INT UNSIGNED DEFAULT 0');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D8955C9486A13');
    }
}
