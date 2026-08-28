<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable played_at on shooting sessions, defaulting to finished_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shooting_sessions ADD played_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE shooting_sessions SET played_at = COALESCE(finished_at, created_at) WHERE played_at IS NULL');
        $this->addSql('ALTER TABLE shooting_sessions MODIFY played_at DATETIME NOT NULL');
        $this->addSql('CREATE INDEX IDX_SHOOTING_SESSIONS_PLAYED_AT ON shooting_sessions (played_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_SHOOTING_SESSIONS_PLAYED_AT ON shooting_sessions');
        $this->addSql('ALTER TABLE shooting_sessions DROP played_at');
    }
}
