<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260806134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default_shot_type to match_players and shot_type to match_balls';
    }

    public function up(Schema $schema): void
    {
        // Add default_shot_type to match_players (default to point)
        $this->addSql("ALTER TABLE match_players ADD default_shot_type VARCHAR(6) NOT NULL DEFAULT 'point';");
        // Add shot_type to match_balls (default to point)
        $this->addSql("ALTER TABLE match_balls ADD shot_type VARCHAR(6) NOT NULL DEFAULT 'point';");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls DROP COLUMN shot_type;');
        $this->addSql('ALTER TABLE match_players DROP COLUMN default_shot_type;');
    }
}
