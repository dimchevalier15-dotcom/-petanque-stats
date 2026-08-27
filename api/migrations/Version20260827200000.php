<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add live_matches table for temporary public live match tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE live_matches (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, status VARCHAR(20) NOT NULL, data JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_LIVE_MATCHES_UUID (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE live_matches');
    }
}
