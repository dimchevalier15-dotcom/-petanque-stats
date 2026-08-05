<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create matches and match_players tables';
    }

    public function up(Schema $schema): void
    {
        // matches table
        $this->addSql('CREATE TABLE matches (
            id INT AUTO_INCREMENT NOT NULL,
            type VARCHAR(20) NOT NULL,
            target_score INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

        // match_players table
        $this->addSql('CREATE TABLE match_players (
            id INT AUTO_INCREMENT NOT NULL,
            match_id INT NOT NULL,
            player_id INT NOT NULL,
            team VARCHAR(1) NOT NULL,
            position SMALLINT NOT NULL,
            INDEX IDX_MP_MATCH (match_id),
            INDEX IDX_MP_PLAYER (player_id),
            CONSTRAINT FK_MP_MATCH FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE,
            CONSTRAINT FK_MP_PLAYER FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT,
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_MP_PLAYER;');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_MP_MATCH;');
        $this->addSql('DROP TABLE match_players;');
        $this->addSql('DROP TABLE matches;');
    }
}
