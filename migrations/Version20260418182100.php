<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418182100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE webauthn_credentials DROP FOREIGN KEY `FK_6F4A5F3A76ED395`');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE publications');
        $this->addSql('DROP TABLE webauthn_credentials');
        $this->addSql('ALTER TABLE categorie CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE profil CHANGE type type VARCHAR(30) NOT NULL, CHANGE statut statut VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY `fk_user_profil`');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commentaire (id INT NOT NULL, publication_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, auteur VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, contenu LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_creation DATETIME NOT NULL, modifie TINYINT DEFAULT NULL, INDEX publication_id (publication_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE publications (id INT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, categorie VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, utilisateur_id INT DEFAULT 0, auteur VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, likes INT DEFAULT 0, commentaires INT DEFAULT 0, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE webauthn_credentials (id VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_id INT NOT NULL, public_key_credential_id LONGBLOB NOT NULL, public_key_credential_descriptor LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', user_handle LONGBLOB DEFAULT NULL, aaguid VARCHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, transports LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_6F4A5F3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE webauthn_credentials ADD CONSTRAINT `FK_6F4A5F3A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorie CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE profil CHANGE type type VARCHAR(30) DEFAULT NULL, CHANGE statut statut VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id INT NOT NULL');
    }
}
