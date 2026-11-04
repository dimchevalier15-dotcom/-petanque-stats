<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add carreau flag to rings_throws for the "tir dans les ronds" variant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rings_throws ADD carreau TINYINT(1) DEFAULT 0 NOT NULL AFTER success');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rings_throws DROP carreau');
    }
}
