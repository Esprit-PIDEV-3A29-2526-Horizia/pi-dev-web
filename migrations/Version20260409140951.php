<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409140951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration déjà satisfaite par la base';
    }

    public function up(Schema $schema): void
    {
        // Rien à faire : prix_total existe déjà
    }

    public function down(Schema $schema): void
    {
        // Rien à faire
    }
}