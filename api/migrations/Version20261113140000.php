<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add public UUID for spectator tournament access';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournaments ADD public_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE tournaments SET public_uuid = LOWER(UUID()) WHERE public_uuid IS NULL');
        $this->addSql('ALTER TABLE tournaments CHANGE public_uuid public_uuid VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_tournament_public_uuid ON tournaments (public_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_tournament_public_uuid ON tournaments');
        $this->addSql('ALTER TABLE tournaments DROP public_uuid');
    }
}
