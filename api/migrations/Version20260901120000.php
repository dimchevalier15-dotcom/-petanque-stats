<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add share_uuid to matches for public match recap sharing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches ADD share_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MATCHES_SHARE_UUID ON matches (share_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_MATCHES_SHARE_UUID ON matches');
        $this->addSql('ALTER TABLE matches DROP share_uuid');
    }
}
