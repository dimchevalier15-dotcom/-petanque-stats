<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add statistics_mode to matches and create match_tracked_players table';
    }

    public function up(Schema $schema): void
    {
        // Add statistics_mode column with default 'standard'
        $this->addSql("ALTER TABLE matches ADD statistics_mode VARCHAR(10) NOT NULL DEFAULT 'standard';");
        // Create match_tracked_players table
        $this->addSql('CREATE TABLE match_tracked_players (
            id INT AUTO_INCREMENT NOT NULL,
            match_id INT NOT NULL,
            player_id INT NOT NULL,
            INDEX IDX_MTP_MATCH (match_id),
            INDEX IDX_MTP_PLAYER (player_id),
            UNIQUE KEY UNIQ_MTP_MATCH_PLAYER (match_id, player_id),
            CONSTRAINT FK_MTP_MATCH FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE,
            CONSTRAINT FK_MTP_PLAYER FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT,
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches DROP COLUMN statistics_mode;');
        $this->addSql('ALTER TABLE match_tracked_players DROP FOREIGN KEY FK_MTP_PLAYER;');
        $this->addSql('ALTER TABLE match_tracked_players DROP FOREIGN KEY FK_MTP_MATCH;');
        $this->addSql('DROP TABLE match_tracked_players;');
    }
}
