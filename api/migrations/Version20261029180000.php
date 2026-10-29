<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add jump_shot_sessions, jump_shot_series and jump_shot_throws for "boule à la sautée" training';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE jump_shot_sessions (
            id INT AUTO_INCREMENT NOT NULL,
            player_id INT NOT NULL,
            current_distance SMALLINT NOT NULL,
            created_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            won TINYINT(1) DEFAULT 0 NOT NULL,
            INDEX IDX_jump_shot_sessions_player (player_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE jump_shot_series (
            id INT AUTO_INCREMENT NOT NULL,
            session_id INT NOT NULL,
            series_number SMALLINT NOT NULL,
            distance SMALLINT NOT NULL,
            success_count SMALLINT NOT NULL,
            is_special_series TINYINT(1) NOT NULL,
            consequence VARCHAR(8) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_jump_shot_series_session (session_id),
            UNIQUE INDEX uniq_jump_shot_series_session_number (session_id, series_number),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE jump_shot_throws (
            id INT AUTO_INCREMENT NOT NULL,
            series_id INT NOT NULL,
            position SMALLINT NOT NULL,
            result VARCHAR(10) NOT NULL,
            recorded_at DATETIME NOT NULL,
            INDEX IDX_jump_shot_throws_series (series_id),
            UNIQUE INDEX uniq_jump_shot_throw_series_position (series_id, position),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE jump_shot_sessions
            ADD CONSTRAINT FK_jump_shot_sessions_player
            FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE jump_shot_series
            ADD CONSTRAINT FK_jump_shot_series_session
            FOREIGN KEY (session_id) REFERENCES jump_shot_sessions (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE jump_shot_throws
            ADD CONSTRAINT FK_jump_shot_throws_series
            FOREIGN KEY (series_id) REFERENCES jump_shot_series (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jump_shot_throws DROP FOREIGN KEY FK_jump_shot_throws_series');
        $this->addSql('ALTER TABLE jump_shot_series DROP FOREIGN KEY FK_jump_shot_series_session');
        $this->addSql('ALTER TABLE jump_shot_sessions DROP FOREIGN KEY FK_jump_shot_sessions_player');
        $this->addSql('DROP TABLE jump_shot_throws');
        $this->addSql('DROP TABLE jump_shot_series');
        $this->addSql('DROP TABLE jump_shot_sessions');
    }
}
