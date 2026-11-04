<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace jump-shot simplified progression mode with easy; add hard mode';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE jump_shot_sessions SET progression_mode = 'easy' WHERE progression_mode = 'simplified'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE jump_shot_sessions SET progression_mode = 'simplified' WHERE progression_mode = 'easy'");
    }
}
