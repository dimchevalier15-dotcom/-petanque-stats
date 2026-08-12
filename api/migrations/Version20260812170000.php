<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merge legacy official match nature into competition';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE matches SET nature = 'competition' WHERE nature = 'official'");
    }

    public function down(Schema $schema): void
    {
        // Irreversible: official was merged into competition.
    }
}
