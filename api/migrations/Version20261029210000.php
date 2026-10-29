<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add progression_mode to jump_shot_sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE jump_shot_sessions ADD progression_mode VARCHAR(16) DEFAULT 'standard' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jump_shot_sessions DROP progression_mode');
    }
}
