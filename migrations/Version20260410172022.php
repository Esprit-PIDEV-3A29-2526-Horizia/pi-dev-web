<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260410172022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservationlog DROP FOREIGN KEY fk_reservation_user');
        $this->addSql('ALTER TABLE vehicule CHANGE id_vehicule id_vehicule INT AUTO_INCREMENT NOT NULL, CHANGE kilometrage kilometrage INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE voyage CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE voyage ADD CONSTRAINT FK_3F9D8955C9486A13 FOREIGN KEY (id_categorie) REFERENCES categorie (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservationlog ADD CONSTRAINT fk_reservation_user FOREIGN KEY (idc) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicule CHANGE id_vehicule id_vehicule INT NOT NULL, CHANGE kilometrage kilometrage INT UNSIGNED DEFAULT 0');
        $this->addSql('ALTER TABLE voyage DROP FOREIGN KEY FK_3F9D8955C9486A13');
        $this->addSql('ALTER TABLE voyage CHANGE id id INT NOT NULL');
    }
}
