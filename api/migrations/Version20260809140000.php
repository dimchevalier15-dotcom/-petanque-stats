<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional match context fields to matches table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches
            ADD comment TEXT DEFAULT NULL,
            ADD team_a_name VARCHAR(100) DEFAULT NULL,
            ADD team_b_name VARCHAR(100) DEFAULT NULL,
            ADD nature VARCHAR(20) DEFAULT NULL,
            ADD competition_name VARCHAR(255) DEFAULT NULL,
            ADD competition_stage VARCHAR(20) DEFAULT NULL,
            ADD terrain_type VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches
            DROP comment,
            DROP team_a_name,
            DROP team_b_name,
            DROP nature,
            DROP competition_name,
            DROP competition_stage,
            DROP terrain_type');
    }
}
