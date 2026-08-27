<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add finished_at to live_matches for completed live match retention';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE live_matches ADD finished_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE live_matches DROP finished_at');
    }
}
