<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timer_started_at to live_matches for overlay elapsed timer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE live_matches ADD timer_started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE live_matches DROP timer_started_at');
    }
}
