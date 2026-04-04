<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401220803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX email ON user');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY `FK_8D93D649275ED078`');
        $this->addSql('DROP INDEX fk_user_profil ON user');
        $this->addSql('CREATE INDEX IDX_8D93D649275ED078 ON user (profil_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT `FK_8D93D649275ED078` FOREIGN KEY (profil_id) REFERENCES profil (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX immatriculation ON vehicule');
        $this->addSql('DROP INDEX id_modele ON vehicule');
        $this->addSql('DROP INDEX idx_vehicule_etat ON vehicule');
        $this->addSql('ALTER TABLE vehicule CHANGE immatriculation immatriculation VARCHAR(20) NOT NULL, CHANGE carburant carburant VARCHAR(255) NOT NULL, CHANGE couleur couleur VARCHAR(30) NOT NULL, CHANGE kilometrage kilometrage INT NOT NULL, CHANGE etat etat VARCHAR(255) NOT NULL, CHANGE prix_par_jour prix_par_jour DOUBLE PRECISION NOT NULL, CHANGE photo photo VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX fk_voyage_categorie ON voyage');
        $this->addSql('ALTER TABLE voyage CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE image_url image_url VARCHAR(255) NOT NULL, CHANGE id_categorie id_categorie INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649275ED078');
        $this->addSql('CREATE UNIQUE INDEX email ON user (email)');
        $this->addSql('DROP INDEX idx_8d93d649275ed078 ON user');
        $this->addSql('CREATE INDEX fk_user_profil ON user (profil_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649275ED078 FOREIGN KEY (profil_id) REFERENCES profil (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicule CHANGE immatriculation immatriculation VARCHAR(20) NOT NULL COMMENT \'ex: 123 TUN 45\', CHANGE carburant carburant ENUM(\'Essence\', \'Diesel\', \'Hybride\', \'Electrique\') NOT NULL, CHANGE couleur couleur VARCHAR(30) DEFAULT NULL, CHANGE kilometrage kilometrage INT UNSIGNED DEFAULT 0, CHANGE etat etat ENUM(\'disponible\', \'louee\', \'en_maintenance\', \'indisponible\') DEFAULT \'disponible\', CHANGE prix_par_jour prix_par_jour NUMERIC(10, 3) NOT NULL COMMENT \'en TND\', CHANGE photo photo VARCHAR(255) DEFAULT NULL COMMENT \'chemin ou URL\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX immatriculation ON vehicule (immatriculation)');
        $this->addSql('CREATE INDEX id_modele ON vehicule (id_modele)');
        $this->addSql('CREATE INDEX idx_vehicule_etat ON vehicule (etat)');
        $this->addSql('ALTER TABLE voyage CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE image_url image_url VARCHAR(255) DEFAULT NULL, CHANGE id_categorie id_categorie INT DEFAULT NULL');
        $this->addSql('CREATE INDEX fk_voyage_categorie ON voyage (id_categorie)');
    }
}
