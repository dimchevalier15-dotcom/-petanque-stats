<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260811140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional title/description context to shooting_sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shooting_sessions
            ADD title VARCHAR(100) DEFAULT NULL,
            ADD description VARCHAR(2000) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE shooting_sessions DROP title, DROP description');
    }
}
