<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add selection globale entities for cross-club coach rosters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE selection_globales (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE selection_globale_coaches (id INT AUTO_INCREMENT NOT NULL, selection_globale_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8F2A1C0E9A6F5F2A (selection_globale_id), INDEX IDX_8F2A1C0EA76ED395 (user_id), UNIQUE INDEX UNIQ_SELECTION_COACH (selection_globale_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE selection_globale_players (id INT AUTO_INCREMENT NOT NULL, selection_globale_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_4B8E2F1E9A6F5F2A (selection_globale_id), INDEX IDX_4B8E2F1E99E6F5DF (player_id), UNIQUE INDEX UNIQ_SELECTION_PLAYER (selection_globale_id, player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE selection_globale_coaches ADD CONSTRAINT FK_8F2A1C0E9A6F5F2A FOREIGN KEY (selection_globale_id) REFERENCES selection_globales (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE selection_globale_coaches ADD CONSTRAINT FK_8F2A1C0EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE selection_globale_players ADD CONSTRAINT FK_4B8E2F1E9A6F5F2A FOREIGN KEY (selection_globale_id) REFERENCES selection_globales (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE selection_globale_players ADD CONSTRAINT FK_4B8E2F1E99E6F5DF FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE selection_globale_coaches DROP FOREIGN KEY FK_8F2A1C0E9A6F5F2A');
        $this->addSql('ALTER TABLE selection_globale_coaches DROP FOREIGN KEY FK_8F2A1C0EA76ED395');
        $this->addSql('ALTER TABLE selection_globale_players DROP FOREIGN KEY FK_4B8E2F1E9A6F5F2A');
        $this->addSql('ALTER TABLE selection_globale_players DROP FOREIGN KEY FK_4B8E2F1E99E6F5DF');
        $this->addSql('DROP TABLE selection_globale_coaches');
        $this->addSql('DROP TABLE selection_globale_players');
        $this->addSql('DROP TABLE selection_globales');
    }
}
