<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403185356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des clés étrangères reservation -> voyage et reservation -> user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495519AA3CB8 FOREIGN KEY (id_voyage) REFERENCES voyage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849556B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX fk_res_user ON reservation (id_user)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495519AA3CB8');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556B3CA4B');
        $this->addSql('DROP INDEX fk_res_user ON reservation');
    }
}