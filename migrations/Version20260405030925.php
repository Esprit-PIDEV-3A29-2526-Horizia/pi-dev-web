<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405030925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE publications');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_publication');
        $this->addSql('ALTER TABLE commentaire CHANGE utilisateur_id utilisateur_id INT DEFAULT NULL, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE modifie modifie TINYINT(1) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_publication ON commentaire');
        $this->addSql('CREATE INDEX IDX_67F068BC38B217A7 ON commentaire (publication_id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT fk_commentaire_publication FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE publications (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, categorie VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, utilisateur_id INT DEFAULT 0, auteur VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, likes INT DEFAULT 0, commentaires INT DEFAULT 0, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC38B217A7');
        $this->addSql('ALTER TABLE commentaire CHANGE utilisateur_id utilisateur_id INT DEFAULT 0, CHANGE contenu contenu TEXT NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE modifie modifie TINYINT(1) DEFAULT 0');
        $this->addSql('DROP INDEX idx_67f068bc38b217a7 ON commentaire');
        $this->addSql('CREATE INDEX idx_publication ON commentaire (publication_id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
    }
}
