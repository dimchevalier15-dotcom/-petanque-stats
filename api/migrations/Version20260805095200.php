<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805095200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create players table with one-to-one nullable unique user_id relation to users';
    }

    public function up(Schema $schema): void
    {
        // MySQL 8 compatible SQL
        $this->addSql('CREATE TABLE players (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            nickname VARCHAR(100) NOT NULL,
            club VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_PLAYERS_USER_ID (user_id),
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
        $this->addSql('ALTER TABLE players ADD CONSTRAINT FK_PLAYERS_USER_ID FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE players DROP FOREIGN KEY FK_PLAYERS_USER_ID;');
        $this->addSql('DROP TABLE players;');
    }
}
