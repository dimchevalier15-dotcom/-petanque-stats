<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add match creator (owner) reference on matches table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches ADD created_by_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_MATCHES_CREATED_BY ON matches (created_by_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_MATCHES_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_MATCHES_CREATED_BY');
        $this->addSql('DROP INDEX IDX_MATCHES_CREATED_BY ON matches');
        $this->addSql('ALTER TABLE matches DROP created_by_id');
    }
}
