<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rings_sessions and rings_throws for "point dans les ronds" training';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE rings_sessions (
            id INT AUTO_INCREMENT NOT NULL,
            player_id INT NOT NULL,
            mode VARCHAR(10) NOT NULL,
            created_at DATETIME NOT NULL,
            played_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            total_score SMALLINT DEFAULT NULL,
            INDEX IDX_rings_sessions_player (player_id),
            INDEX IDX_rings_sessions_played_at (played_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE rings_throws (
            id INT AUTO_INCREMENT NOT NULL,
            session_id INT NOT NULL,
            position SMALLINT NOT NULL,
            target VARCHAR(10) NOT NULL,
            success TINYINT(1) NOT NULL,
            score SMALLINT NOT NULL,
            INDEX IDX_rings_throws_session (session_id),
            UNIQUE INDEX uniq_rings_throw_session_position (session_id, position),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE rings_sessions
            ADD CONSTRAINT FK_rings_sessions_player
            FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE rings_throws
            ADD CONSTRAINT FK_rings_throws_session
            FOREIGN KEY (session_id) REFERENCES rings_sessions (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rings_throws DROP FOREIGN KEY FK_rings_throws_session');
        $this->addSql('ALTER TABLE rings_sessions DROP FOREIGN KEY FK_rings_sessions_player');
        $this->addSql('DROP TABLE rings_throws');
        $this->addSql('DROP TABLE rings_sessions');
    }
}
