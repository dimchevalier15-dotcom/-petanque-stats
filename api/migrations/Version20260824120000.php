<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email_verified_at on users and auth_tokens table for email verification and password reset';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD email_verified_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL');
        $this->addSql('CREATE TABLE auth_tokens (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, purpose VARCHAR(32) NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_AUTH_TOKENS_HASH (token_hash), INDEX IDX_AUTH_TOKENS_USER_PURPOSE (user_id, purpose), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE auth_tokens ADD CONSTRAINT FK_AUTH_TOKENS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_tokens DROP FOREIGN KEY FK_AUTH_TOKENS_USER');
        $this->addSql('DROP TABLE auth_tokens');
        $this->addSql('ALTER TABLE users DROP email_verified_at');
    }
}
