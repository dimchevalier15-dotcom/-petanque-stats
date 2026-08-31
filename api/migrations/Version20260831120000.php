<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add match participation validation (has_validated_match on match_players, requires_match_validation on users)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_players ADD has_validated_match TINYINT(1) DEFAULT NULL');
        $this->addSql('UPDATE match_players SET has_validated_match = 1 WHERE has_validated_match IS NULL');
        $this->addSql('ALTER TABLE users ADD requires_match_validation TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP requires_match_validation');
        $this->addSql('ALTER TABLE match_players DROP has_validated_match');
    }
}
