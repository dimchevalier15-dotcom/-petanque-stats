<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tournament championship, days, matches and play-off tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tournaments (id INT AUTO_INCREMENT NOT NULL, created_by_user_id INT NOT NULL, name VARCHAR(120) NOT NULL, description LONGTEXT DEFAULT NULL, format VARCHAR(64) NOT NULL, status VARCHAR(20) NOT NULL, min_players_per_team INT NOT NULL, max_players_per_team INT NOT NULL, points_for_win INT NOT NULL, points_for_draw INT NOT NULL, draws_allowed TINYINT(1) NOT NULL, number_of_lanes INT NOT NULL, excluded_lane_numbers JSON NOT NULL, created_at DATETIME NOT NULL, starts_at DATE DEFAULT NULL, ends_at DATE DEFAULT NULL, INDEX IDX_TOURNAMENT_CREATOR (created_by_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_members (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, user_id INT NOT NULL, is_admin TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_TOURNAMENT_MEMBER_TOURNAMENT (tournament_id), INDEX IDX_TOURNAMENT_MEMBER_USER (user_id), UNIQUE INDEX uniq_tournament_member (tournament_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_teams (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, name VARCHAR(120) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_TOURNAMENT_TEAM_TOURNAMENT (tournament_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_team_players (id INT AUTO_INCREMENT NOT NULL, team_id INT NOT NULL, tournament_id INT NOT NULL, player_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_TOURNAMENT_TEAM_PLAYER_TEAM (team_id), INDEX IDX_TOURNAMENT_TEAM_PLAYER_TOURNAMENT (tournament_id), INDEX IDX_TOURNAMENT_TEAM_PLAYER_PLAYER (player_id), UNIQUE INDEX uniq_tournament_team_player (team_id, player_id), UNIQUE INDEX uniq_tournament_player (tournament_id, player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_days (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, number INT NOT NULL, phase VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, playoff_round VARCHAR(20) DEFAULT NULL, excluded_lane_numbers JSON NOT NULL, created_at DATETIME NOT NULL, closed_at DATETIME DEFAULT NULL, INDEX IDX_TOURNAMENT_DAY_TOURNAMENT (tournament_id), UNIQUE INDEX uniq_tournament_day_number (tournament_id, phase, number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_day_fake_teams (id INT AUTO_INCREMENT NOT NULL, day_id INT NOT NULL, name VARCHAR(80) NOT NULL, INDEX IDX_TOURNAMENT_FAKE_DAY (day_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_matches (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, day_id INT NOT NULL, team1_id INT DEFAULT NULL, team2_id INT DEFAULT NULL, fake_team1_id INT DEFAULT NULL, fake_team2_id INT DEFAULT NULL, championship_team_low_id INT DEFAULT NULL, championship_team_high_id INT DEFAULT NULL, score1 INT DEFAULT NULL, score2 INT DEFAULT NULL, lane_number INT NOT NULL, status VARCHAR(20) NOT NULL, fictional TINYINT(1) NOT NULL, phase VARCHAR(20) NOT NULL, playoff_round VARCHAR(20) DEFAULT NULL, playoff_slot INT DEFAULT NULL, home_source_match_id INT DEFAULT NULL, away_source_match_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_TOURNAMENT_MATCH_TOURNAMENT (tournament_id), INDEX idx_tournament_match_day (day_id), INDEX idx_tournament_match_phase (tournament_id, phase), INDEX IDX_TOURNAMENT_MATCH_TEAM1 (team1_id), INDEX IDX_TOURNAMENT_MATCH_TEAM2 (team2_id), INDEX IDX_TOURNAMENT_MATCH_FAKE1 (fake_team1_id), INDEX IDX_TOURNAMENT_MATCH_FAKE2 (fake_team2_id), INDEX IDX_TOURNAMENT_MATCH_HOME_SOURCE (home_source_match_id), INDEX IDX_TOURNAMENT_MATCH_AWAY_SOURCE (away_source_match_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_day_team_slots (id INT AUTO_INCREMENT NOT NULL, day_id INT NOT NULL, team_id INT NOT NULL, match_id INT NOT NULL, INDEX IDX_TOURNAMENT_SLOT_DAY (day_id), INDEX IDX_TOURNAMENT_SLOT_TEAM (team_id), INDEX IDX_TOURNAMENT_SLOT_MATCH (match_id), UNIQUE INDEX uniq_tournament_day_team (day_id, team_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament_championship_pairings (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, match_id INT NOT NULL, team_low_id INT NOT NULL, team_high_id INT NOT NULL, INDEX IDX_TOURNAMENT_PAIRING_TOURNAMENT (tournament_id), UNIQUE INDEX uniq_championship_pairing (tournament_id, team_low_id, team_high_id), UNIQUE INDEX uniq_championship_pairing_match (match_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournaments ADD CONSTRAINT FK_TOURNAMENT_CREATOR FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE tournament_members ADD CONSTRAINT FK_TOURNAMENT_MEMBER_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_members ADD CONSTRAINT FK_TOURNAMENT_MEMBER_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_teams ADD CONSTRAINT FK_TOURNAMENT_TEAM_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_team_players ADD CONSTRAINT FK_TOURNAMENT_TEAM_PLAYER_TEAM FOREIGN KEY (team_id) REFERENCES tournament_teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_team_players ADD CONSTRAINT FK_TOURNAMENT_TEAM_PLAYER_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_team_players ADD CONSTRAINT FK_TOURNAMENT_TEAM_PLAYER_PLAYER FOREIGN KEY (player_id) REFERENCES players (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_days ADD CONSTRAINT FK_TOURNAMENT_DAY_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_day_fake_teams ADD CONSTRAINT FK_TOURNAMENT_FAKE_DAY FOREIGN KEY (day_id) REFERENCES tournament_days (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_DAY FOREIGN KEY (day_id) REFERENCES tournament_days (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_TEAM1 FOREIGN KEY (team1_id) REFERENCES tournament_teams (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_TEAM2 FOREIGN KEY (team2_id) REFERENCES tournament_teams (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_FAKE1 FOREIGN KEY (fake_team1_id) REFERENCES tournament_day_fake_teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_FAKE2 FOREIGN KEY (fake_team2_id) REFERENCES tournament_day_fake_teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_HOME_SOURCE FOREIGN KEY (home_source_match_id) REFERENCES tournament_matches (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT FK_TOURNAMENT_MATCH_AWAY_SOURCE FOREIGN KEY (away_source_match_id) REFERENCES tournament_matches (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT chk_tournament_score1 CHECK (score1 IS NULL OR (score1 >= 0 AND score1 <= 13))');
        $this->addSql('ALTER TABLE tournament_matches ADD CONSTRAINT chk_tournament_score2 CHECK (score2 IS NULL OR (score2 >= 0 AND score2 <= 13))');
        $this->addSql('ALTER TABLE tournament_day_team_slots ADD CONSTRAINT FK_TOURNAMENT_SLOT_DAY FOREIGN KEY (day_id) REFERENCES tournament_days (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_day_team_slots ADD CONSTRAINT FK_TOURNAMENT_SLOT_TEAM FOREIGN KEY (team_id) REFERENCES tournament_teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_day_team_slots ADD CONSTRAINT FK_TOURNAMENT_SLOT_MATCH FOREIGN KEY (match_id) REFERENCES tournament_matches (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_championship_pairings ADD CONSTRAINT FK_TOURNAMENT_PAIRING_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournaments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_championship_pairings ADD CONSTRAINT FK_TOURNAMENT_PAIRING_MATCH FOREIGN KEY (match_id) REFERENCES tournament_matches (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament_championship_pairings DROP FOREIGN KEY FK_TOURNAMENT_PAIRING_MATCH');
        $this->addSql('ALTER TABLE tournament_championship_pairings DROP FOREIGN KEY FK_TOURNAMENT_PAIRING_TOURNAMENT');
        $this->addSql('ALTER TABLE tournament_day_team_slots DROP FOREIGN KEY FK_TOURNAMENT_SLOT_MATCH');
        $this->addSql('ALTER TABLE tournament_day_team_slots DROP FOREIGN KEY FK_TOURNAMENT_SLOT_TEAM');
        $this->addSql('ALTER TABLE tournament_day_team_slots DROP FOREIGN KEY FK_TOURNAMENT_SLOT_DAY');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_AWAY_SOURCE');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_HOME_SOURCE');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_FAKE2');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_FAKE1');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_TEAM2');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_TEAM1');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_DAY');
        $this->addSql('ALTER TABLE tournament_matches DROP FOREIGN KEY FK_TOURNAMENT_MATCH_TOURNAMENT');
        $this->addSql('ALTER TABLE tournament_day_fake_teams DROP FOREIGN KEY FK_TOURNAMENT_FAKE_DAY');
        $this->addSql('ALTER TABLE tournament_days DROP FOREIGN KEY FK_TOURNAMENT_DAY_TOURNAMENT');
        $this->addSql('ALTER TABLE tournament_team_players DROP FOREIGN KEY FK_TOURNAMENT_TEAM_PLAYER_PLAYER');
        $this->addSql('ALTER TABLE tournament_team_players DROP FOREIGN KEY FK_TOURNAMENT_TEAM_PLAYER_TOURNAMENT');
        $this->addSql('ALTER TABLE tournament_team_players DROP FOREIGN KEY FK_TOURNAMENT_TEAM_PLAYER_TEAM');
        $this->addSql('ALTER TABLE tournament_teams DROP FOREIGN KEY FK_TOURNAMENT_TEAM_TOURNAMENT');
        $this->addSql('ALTER TABLE tournament_members DROP FOREIGN KEY FK_TOURNAMENT_MEMBER_USER');
        $this->addSql('ALTER TABLE tournament_members DROP FOREIGN KEY FK_TOURNAMENT_MEMBER_TOURNAMENT');
        $this->addSql('ALTER TABLE tournaments DROP FOREIGN KEY FK_TOURNAMENT_CREATOR');
        $this->addSql('DROP TABLE tournament_championship_pairings');
        $this->addSql('DROP TABLE tournament_day_team_slots');
        $this->addSql('DROP TABLE tournament_matches');
        $this->addSql('DROP TABLE tournament_day_fake_teams');
        $this->addSql('DROP TABLE tournament_days');
        $this->addSql('DROP TABLE tournament_team_players');
        $this->addSql('DROP TABLE tournament_teams');
        $this->addSql('DROP TABLE tournament_members');
        $this->addSql('DROP TABLE tournaments');
    }
}
