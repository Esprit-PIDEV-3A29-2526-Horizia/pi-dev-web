<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260410175816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de nb_adultes et nb_enfants dans reservation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD nb_adultes INT NOT NULL DEFAULT 1, ADD nb_enfants INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE reservation CHANGE nbr_personnes nbr_personnes INT NOT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE date_reservation date_reservation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE statut statut VARCHAR(50) NOT NULL');

        // Supprime prix_total seulement s'il existe encore
        $this->addSql('ALTER TABLE reservation DROP COLUMN IF EXISTS prix_total');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD COLUMN IF NOT EXISTS prix_total DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation DROP COLUMN nb_adultes, DROP COLUMN nb_enfants');
    }
}