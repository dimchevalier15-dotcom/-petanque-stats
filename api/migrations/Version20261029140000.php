<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pointing_sessions and pointing_shots for "point de précision" training';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pointing_sessions (
            id INT AUTO_INCREMENT NOT NULL,
            player_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            played_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            total_score SMALLINT DEFAULT NULL,
            title VARCHAR(100) DEFAULT NULL,
            description VARCHAR(2000) DEFAULT NULL,
            context_nature VARCHAR(11) DEFAULT NULL,
            INDEX IDX_pointing_sessions_player (player_id),
            INDEX IDX_pointing_sessions_played_at (played_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE pointing_shots (
            id INT AUTO_INCREMENT NOT NULL,
            session_id INT NOT NULL,
            workshop SMALLINT NOT NULL,
            distance SMALLINT NOT NULL,
            result VARCHAR(10) NOT NULL,
            score SMALLINT NOT NULL,
            INDEX IDX_pointing_shots_session (session_id),
            UNIQUE INDEX uniq_pointing_shot_session_workshop_distance (session_id, workshop, distance),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE pointing_sessions
            ADD CONSTRAINT FK_pointing_sessions_player
            FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE pointing_shots
            ADD CONSTRAINT FK_pointing_shots_session
            FOREIGN KEY (session_id) REFERENCES pointing_sessions (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pointing_shots DROP FOREIGN KEY FK_pointing_shots_session');
        $this->addSql('ALTER TABLE pointing_sessions DROP FOREIGN KEY FK_pointing_sessions_player');
        $this->addSql('DROP TABLE pointing_shots');
        $this->addSql('DROP TABLE pointing_sessions');
    }
}
