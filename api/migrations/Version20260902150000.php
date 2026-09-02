<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timer_accumulated_ms to live_matches for paused match timer state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE live_matches ADD timer_accumulated_ms INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE live_matches DROP timer_accumulated_ms');
    }
}
