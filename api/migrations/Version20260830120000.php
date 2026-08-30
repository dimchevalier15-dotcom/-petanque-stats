<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store opening scores for matches joined in progress';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches ADD opening_score_a INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE matches ADD opening_score_b INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches DROP opening_score_b');
        $this->addSql('ALTER TABLE matches DROP opening_score_a');
    }
}
