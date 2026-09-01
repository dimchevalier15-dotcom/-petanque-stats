<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename match_balls.ball_index to sequence_order (global shot order within an end)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls CHANGE ball_index sequence_order SMALLINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_balls CHANGE sequence_order ball_index SMALLINT NOT NULL');
    }
}
