<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415201428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie DROP image_url');
        $this->addSql('ALTER TABLE events ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `FK_42C8495519AA3CB8`');
        $this->addSql('DROP INDEX FK_42C8495519AA3CB8 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP prix_total');
        $this->addSql('ALTER TABLE user CHANGE id id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie ADD image_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE events DROP latitude, DROP longitude');
        $this->addSql('ALTER TABLE reservation ADD prix_total DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT `FK_42C8495519AA3CB8` FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX FK_42C8495519AA3CB8 ON reservation (id_voyage)');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}
