<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Flag cochonnet shots separately from standard tir statistics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls ADD is_cochonnet TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls DROP is_cochonnet');
    }
}
