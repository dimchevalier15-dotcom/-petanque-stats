<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add training_sessions and training_attempts tables for the training mode feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE training_sessions (
            id INT AUTO_INCREMENT NOT NULL,
            player_id INT NOT NULL,
            type VARCHAR(6) NOT NULL,
            distance DOUBLE NOT NULL,
            planned_balls SMALLINT NOT NULL,
            created_at DATETIME NOT NULL,
            finished_at DATETIME DEFAULT NULL,
            total_score SMALLINT DEFAULT NULL,
            INDEX IDX_training_sessions_player (player_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE training_attempts (
            id INT AUTO_INCREMENT NOT NULL,
            session_id INT NOT NULL,
            number SMALLINT NOT NULL,
            type VARCHAR(6) NOT NULL,
            distance DOUBLE NOT NULL,
            result VARCHAR(12) NOT NULL,
            score SMALLINT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_training_attempts_session (session_id),
            UNIQUE INDEX uniq_training_attempt_session_number (session_id, number),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE training_sessions
            ADD CONSTRAINT FK_training_sessions_player
            FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT');

        $this->addSql('ALTER TABLE training_attempts
            ADD CONSTRAINT FK_training_attempts_session
            FOREIGN KEY (session_id) REFERENCES training_sessions (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training_attempts DROP FOREIGN KEY FK_training_attempts_session');
        $this->addSql('ALTER TABLE training_sessions DROP FOREIGN KEY FK_training_sessions_player');
        $this->addSql('DROP TABLE training_attempts');
        $this->addSql('DROP TABLE training_sessions');
    }
}
