<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402184244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ image_url dans la table categorie';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categorie ADD image_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE categorie DROP image_url');
    }
}