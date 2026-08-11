<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional distance (in meters) on match_balls; existing balls stay NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls ADD distance DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls DROP distance');
    }
}
