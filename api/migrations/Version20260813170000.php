<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add starting_role on match_players and per-end player roles for stats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE match_players ADD starting_role VARCHAR(10) DEFAULT NULL");
        $this->addSql("UPDATE match_players SET starting_role = 'tireur' WHERE default_shot_type = 'tir'");
        $this->addSql("UPDATE match_players SET starting_role = 'pointeur' WHERE starting_role IS NULL");

        $this->addSql(<<<'SQL'
            CREATE TABLE match_end_player_roles (
                id INT AUTO_INCREMENT NOT NULL,
                end_id INT NOT NULL,
                player_id INT NOT NULL,
                role VARCHAR(10) NOT NULL,
                INDEX IDX_END_PLAYER_ROLES_END (end_id),
                INDEX IDX_END_PLAYER_ROLES_PLAYER (player_id),
                UNIQUE INDEX uniq_end_player_role (end_id, player_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql('ALTER TABLE match_end_player_roles ADD CONSTRAINT FK_END_PLAYER_ROLES_END FOREIGN KEY (end_id) REFERENCES match_ends (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_end_player_roles ADD CONSTRAINT FK_END_PLAYER_ROLES_PLAYER FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE match_end_player_roles DROP FOREIGN KEY FK_END_PLAYER_ROLES_END');
        $this->addSql('ALTER TABLE match_end_player_roles DROP FOREIGN KEY FK_END_PLAYER_ROLES_PLAYER');
        $this->addSql('DROP TABLE match_end_player_roles');
        $this->addSql('ALTER TABLE match_players DROP starting_role');
    }
}
