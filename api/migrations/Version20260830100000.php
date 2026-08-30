<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add six placeholder players (A–F) for unresolved match participants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE players ADD placeholder_key VARCHAR(1) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PLAYERS_PLACEHOLDER_KEY ON players (placeholder_key)');

        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($letters as $upper) {
            $key = strtolower($upper);
            $this->addSql(sprintf(
                "INSERT INTO players (first_name, last_name, nickname, created_at, placeholder_key) VALUES ('Player', '%s', '%s', NOW(), '%s')",
                $upper,
                $upper,
                $key,
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM players WHERE placeholder_key IN ('a', 'b', 'c', 'd', 'e', 'f')");
        $this->addSql('DROP INDEX UNIQ_PLAYERS_PLACEHOLDER_KEY ON players');
        $this->addSql('ALTER TABLE players DROP placeholder_key');
    }
}
