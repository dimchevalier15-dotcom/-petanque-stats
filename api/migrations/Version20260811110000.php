<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shooting_sessions and shooting_shots tables for the "tir de précision" feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE shooting_sessions (
            id INT AUTO_INCREMENT NOT NULL,
            player_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            total_score SMALLINT DEFAULT NULL,
            INDEX IDX_shooting_sessions_player (player_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE shooting_shots (
            id INT AUTO_INCREMENT NOT NULL,
            session_id INT NOT NULL,
            workshop SMALLINT NOT NULL,
            distance SMALLINT NOT NULL,
            result VARCHAR(10) NOT NULL,
            score SMALLINT NOT NULL,
            INDEX IDX_shooting_shots_session (session_id),
            UNIQUE INDEX uniq_shooting_shot_session_workshop_distance (session_id, workshop, distance),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE shooting_sessions
            ADD CONSTRAINT FK_shooting_sessions_player
            FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE shooting_shots
            ADD CONSTRAINT FK_shooting_shots_session
            FOREIGN KEY (session_id) REFERENCES shooting_sessions (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shooting_shots DROP FOREIGN KEY FK_shooting_shots_session');
        $this->addSql('ALTER TABLE shooting_sessions DROP FOREIGN KEY FK_shooting_sessions_player');
        $this->addSql('DROP TABLE shooting_shots');
        $this->addSql('DROP TABLE shooting_sessions');
    }
}
