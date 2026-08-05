<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805001700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table with UUID id, unique email, hashed password, and created_at';
    }

    public function up(Schema $schema): void
    {
        // this migration is auto-generated, adjust SQL for MySQL 8
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_USERS_EMAIL (email), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
