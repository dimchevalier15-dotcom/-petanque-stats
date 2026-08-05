<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create match_ends and match_balls tables';
    }

    public function up(Schema $schema): void
    {
        // match_ends table
        $this->addSql('CREATE TABLE match_ends (
            id INT AUTO_INCREMENT NOT NULL,
            match_id INT NOT NULL,
            end_index INT NOT NULL,
            winner VARCHAR(1) NOT NULL,
            points SMALLINT NOT NULL,
            INDEX IDX_ME_MATCH (match_id),
            CONSTRAINT FK_ME_MATCH FOREIGN KEY (match_id) REFERENCES matches (id) ON DELETE CASCADE,
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');

        // match_balls table
        $this->addSql('CREATE TABLE match_balls (
            id INT AUTO_INCREMENT NOT NULL,
            end_id INT NOT NULL,
            player_id INT NOT NULL,
            ball_index SMALLINT NOT NULL,
            note SMALLINT NOT NULL,
            INDEX IDX_MB_END (end_id),
            INDEX IDX_MB_PLAYER (player_id),
            CONSTRAINT FK_MB_END FOREIGN KEY (end_id) REFERENCES match_ends (id) ON DELETE CASCADE,
            CONSTRAINT FK_MB_PLAYER FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT,
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls DROP FOREIGN KEY FK_MB_PLAYER;');
        $this->addSql('ALTER TABLE match_balls DROP FOREIGN KEY FK_MB_END;');
        $this->addSql('DROP TABLE match_balls;');
        $this->addSql('ALTER TABLE match_ends DROP FOREIGN KEY FK_ME_MATCH;');
        $this->addSql('DROP TABLE match_ends;');
    }
}
