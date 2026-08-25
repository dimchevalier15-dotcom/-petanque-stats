<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add competitions catalog and optional competition_id on matches';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE competitions (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            event_date DATE NOT NULL,
            country VARCHAR(100) NOT NULL,
            context VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE matches ADD competition_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_MATCHES_COMPETITION FOREIGN KEY (competition_id) REFERENCES competitions (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_MATCHES_COMPETITION ON matches (competition_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_MATCHES_COMPETITION');
        $this->addSql('DROP INDEX IDX_MATCHES_COMPETITION ON matches');
        $this->addSql('ALTER TABLE matches DROP competition_id');
        $this->addSql('DROP TABLE competitions');
    }
}
