<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coach_for_club_id on users for club coach access';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD coach_for_club_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9F5E2A8A4 FOREIGN KEY (coach_for_club_id) REFERENCES clubs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9F5E2A8A4 ON users (coach_for_club_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9F5E2A8A4');
        $this->addSql('DROP INDEX IDX_1483A5E9F5E2A8A4 ON users');
        $this->addSql('ALTER TABLE users DROP coach_for_club_id');
    }
}
