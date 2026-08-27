<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add countries and clubs; replace player club string with club relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE countries (id INT AUTO_INCREMENT NOT NULL, iso_code VARCHAR(2) NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_COUNTRIES_ISO_CODE (iso_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE clubs (id INT AUTO_INCREMENT NOT NULL, country_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_CLUBS_COUNTRY (country_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clubs ADD CONSTRAINT FK_CLUBS_COUNTRY FOREIGN KEY (country_id) REFERENCES countries (id)');

        foreach ($this->countries() as [$isoCode, $name]) {
            $this->addSql('INSERT INTO countries (iso_code, name) VALUES (?, ?)', [$isoCode, $name]);
        }

        $this->addSql('ALTER TABLE players ADD club_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE players ADD CONSTRAINT FK_PLAYERS_CLUB FOREIGN KEY (club_id) REFERENCES clubs (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_PLAYERS_CLUB ON players (club_id)');
        $this->addSql('ALTER TABLE players DROP club');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE players DROP FOREIGN KEY FK_PLAYERS_CLUB');
        $this->addSql('DROP INDEX IDX_PLAYERS_CLUB ON players');
        $this->addSql('ALTER TABLE players DROP club_id');
        $this->addSql('ALTER TABLE players ADD club VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE clubs DROP FOREIGN KEY FK_CLUBS_COUNTRY');
        $this->addSql('DROP TABLE clubs');
        $this->addSql('DROP TABLE countries');
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function countries(): array
    {
        return [
            ['AL', 'Albania'],
            ['AD', 'Andorra'],
            ['AM', 'Armenia'],
            ['AT', 'Austria'],
            ['AZ', 'Azerbaijan'],
            ['BY', 'Belarus'],
            ['BE', 'Belgium'],
            ['BA', 'Bosnia and Herzegovina'],
            ['BG', 'Bulgaria'],
            ['HR', 'Croatia'],
            ['CY', 'Cyprus'],
            ['CZ', 'Czechia'],
            ['DK', 'Denmark'],
            ['EE', 'Estonia'],
            ['FI', 'Finland'],
            ['FR', 'France'],
            ['GE', 'Georgia'],
            ['DE', 'Germany'],
            ['GR', 'Greece'],
            ['HU', 'Hungary'],
            ['IS', 'Iceland'],
            ['IE', 'Ireland'],
            ['IT', 'Italy'],
            ['XK', 'Kosovo'],
            ['LV', 'Latvia'],
            ['LI', 'Liechtenstein'],
            ['LT', 'Lithuania'],
            ['LU', 'Luxembourg'],
            ['MG', 'Madagascar'],
            ['MT', 'Malta'],
            ['MD', 'Moldova'],
            ['MC', 'Monaco'],
            ['ME', 'Montenegro'],
            ['NL', 'Netherlands'],
            ['MK', 'North Macedonia'],
            ['NO', 'Norway'],
            ['PL', 'Poland'],
            ['PT', 'Portugal'],
            ['RO', 'Romania'],
            ['RU', 'Russia'],
            ['SM', 'San Marino'],
            ['RS', 'Serbia'],
            ['SK', 'Slovakia'],
            ['SI', 'Slovenia'],
            ['ES', 'Spain'],
            ['SE', 'Sweden'],
            ['CH', 'Switzerland'],
            ['TH', 'Thailand'],
            ['TR', 'Turkey'],
            ['UA', 'Ukraine'],
            ['GB', 'United Kingdom'],
            ['US', 'United States'],
            ['VA', 'Vatican City'],
        ];
    }
}
