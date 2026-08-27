<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user role column, default SIMPLE_PLAYER';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD role VARCHAR(20) NOT NULL DEFAULT 'SIMPLE_PLAYER'");
        $this->addSql("UPDATE users SET role = 'MASTER' WHERE LOWER(email) = 'dimchevalier15@gmail.com'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP role');
    }
}
