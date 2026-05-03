<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418151358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY `fk_participation_user`');
        $this->addSql('ALTER TABLE participation ADD email_snapshot VARCHAR(255) DEFAULT NULL, ADD nom_snapshot VARCHAR(100) DEFAULT NULL, ADD prenom_snapshot VARCHAR(100) DEFAULT NULL, ADD telephone_snapshot VARCHAR(20) DEFAULT NULL');
        $this->addSql('DROP INDEX fk_participation_user ON participation');
        $this->addSql('CREATE INDEX IDX_AB55E24FA76ED395 ON participation (user_id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT `fk_participation_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FA76ED395');
        $this->addSql('ALTER TABLE participation DROP email_snapshot, DROP nom_snapshot, DROP prenom_snapshot, DROP telephone_snapshot');
        $this->addSql('DROP INDEX idx_ab55e24fa76ed395 ON participation');
        $this->addSql('CREATE INDEX fk_participation_user ON participation (user_id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }
}
