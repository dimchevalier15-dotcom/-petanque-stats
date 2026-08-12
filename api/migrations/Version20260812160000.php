<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional training/competition context nature to shooting_sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shooting_sessions ADD context_nature VARCHAR(11) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shooting_sessions DROP context_nature');
    }
}
