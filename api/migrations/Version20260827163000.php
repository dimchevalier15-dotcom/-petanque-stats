<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add editable played_at on matches, defaulting to created_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches ADD played_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE matches SET played_at = created_at WHERE played_at IS NULL');
        $this->addSql('ALTER TABLE matches MODIFY played_at DATETIME NOT NULL');
        $this->addSql('CREATE INDEX IDX_MATCHES_PLAYED_AT ON matches (played_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_MATCHES_PLAYED_AT ON matches');
        $this->addSql('ALTER TABLE matches DROP played_at');
    }
}
