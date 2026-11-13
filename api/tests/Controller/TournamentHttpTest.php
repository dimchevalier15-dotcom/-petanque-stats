<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Player;
use App\Entity\User;
use App\Enum\UserRole;
use App\Tests\Support\WebDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TournamentHttpTest extends WebDatabaseTestCase
{

    public function testGuestsCannotListTournaments(): void
    {
        $client = $this->createDatabaseClient();
        $client->request('GET', '/api/tournaments');

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testOnlyTheMasterCreatesATournamentAndOnlyMembersCanReadIt(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-'.$suffix, UserRole::SIMPLE_PLAYER);
        $member = $this->user('member-'.$suffix, UserRole::SIMPLE_PLAYER);
        $outsider = $this->user('outsider-'.$suffix, UserRole::SIMPLE_PLAYER);
        $this->em()->flush();

        $this->json($client, 'POST', '/api/tournaments', $this->token($outsider), $this->settings([$admin->getId()]));
        self::assertSame(403, $client->getResponse()->getStatusCode());

        $this->json($client, 'POST', '/api/tournaments', $this->token($master), $this->settings([$admin->getId(), $master->getId()]));
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $created = $this->payload($client);
        self::assertSame('Coupe club', $created['name']);
        self::assertSame('championship_single_playoffs_8', $created['format']);
        self::assertTrue($created['viewerIsAdmin']);
        $tournamentId = $created['id'];

        $client->request('GET', '/api/tournaments/'.$tournamentId, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($outsider)]);
        self::assertSame(404, $client->getResponse()->getStatusCode());

        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/members', $this->token($admin), [
            'userId' => $member->getId(),
            'admin' => false,
        ]);
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        $client->request('GET', '/api/tournaments/'.$tournamentId, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($member)]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
        self::assertNull($this->payload($client)['members']);

        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/days', $this->token($member), [
            'absentTeamIds' => [],
            'fakeTeamNames' => [],
            'excludedLaneNumbers' => [],
        ]);
        self::assertSame(403, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/tournaments', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($member)]);
        $listed = $this->payload($client);
        self::assertSame($tournamentId, $listed[0]['id']);
        $client->request('GET', '/api/tournaments', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($outsider)]);
        self::assertSame([], $this->payload($client));
    }

    public function testTeamLimitsAndAPlayerCanJoinSeveralTournamentsButNotTwoTeams(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-teams-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-teams-'.$suffix, UserRole::SIMPLE_PLAYER);
        $playerUser = $this->user('player-teams-'.$suffix, UserRole::SIMPLE_PLAYER);
        $player = new Player('Lea', 'Martin', 'Lea'.$suffix);
        $player->setUser($playerUser);
        $other = new Player('Noa', 'Bernard', 'Noa'.$suffix);
        $this->em()->persist($player);
        $this->em()->persist($other);
        $this->em()->flush();
        $first = $this->createTournament($client, $master, [$admin->getId()], 1, 2);
        $second = $this->createTournament($client, $master, [$admin->getId()], 1, 2);

        $this->json($client, 'POST', '/api/tournaments/'.$first.'/teams', $this->token($admin), [
            'name' => 'A',
            'playerIds' => [],
        ]);
        self::assertSame(422, $client->getResponse()->getStatusCode());

        $this->json($client, 'POST', '/api/tournaments/'.$first.'/teams', $this->token($admin), [
            'name' => 'A',
            'playerIds' => [$player->getId(), $other->getId()],
        ]);
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $teamId = $this->payload($client)['teams'][0]['id'];
        $members = $this->payload($client)['members'];
        self::assertIsArray($members);
        $enrolled = array_values(array_filter(
            $members,
            static fn (mixed $member): bool => is_array($member) && $member['userId'] === $playerUser->getId(),
        ));
        self::assertCount(1, $enrolled);
        self::assertFalse($enrolled[0]['admin']);
        $client->request('GET', '/api/tournaments/'.$first, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($playerUser)]);
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $this->json($client, 'DELETE', '/api/tournaments/'.$first.'/members/'.$playerUser->getId(), $this->token($admin));
        self::assertSame(409, $client->getResponse()->getStatusCode());
        self::assertSame('player_still_in_team', $this->payload($client)['error']);

        $this->json($client, 'POST', '/api/tournaments/'.$first.'/teams', $this->token($admin), [
            'name' => 'B',
            'playerIds' => [$player->getId()],
        ]);
        self::assertSame(409, $client->getResponse()->getStatusCode());
        self::assertSame('player_already_assigned', $this->payload($client)['error']);

        $this->json($client, 'POST', '/api/tournaments/'.$second.'/teams', $this->token($admin), [
            'name' => 'Ailleurs',
            'playerIds' => [$player->getId()],
        ]);
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        $this->json($client, 'DELETE', '/api/tournaments/'.$first.'/teams/'.$teamId.'/players/'.$other->getId(), $this->token($admin));
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertCount(1, $this->payload($client)['teams'][0]['players']);
    }

    public function testPublicTournamentDetailIsReadableWithoutAccount(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-public-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-public-'.$suffix, UserRole::SIMPLE_PLAYER);
        $this->em()->flush();

        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 2);
        $client->request('GET', '/api/tournaments/'.$tournamentId, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($admin)]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $publicUuid = $this->payload($client)['publicUuid'];
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $publicUuid,
        );

        $client->request('GET', '/api/public/tournaments/'.$publicUuid);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $public = $this->payload($client);
        self::assertSame($tournamentId, $public['id']);
        self::assertFalse($public['viewerIsAdmin']);
        self::assertNull($public['viewerTeamId']);
        self::assertNull($public['members']);

        $client->request('GET', '/api/public/tournaments/00000000-0000-4000-8000-000000000000');
        self::assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testRosterPlayerGainsTournamentAccessAfterLinkingAccount(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-link-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-link-'.$suffix, UserRole::SIMPLE_PLAYER);
        $latecomer = $this->user('late-link-'.$suffix, UserRole::SIMPLE_PLAYER);
        $placeholder = new Player('Tom', 'Durand', 'Tom'.$suffix);
        $teammate = new Player('Eva', 'Petit', 'Eva'.$suffix);
        $this->em()->persist($placeholder);
        $this->em()->persist($teammate);
        $this->em()->flush();

        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 2);

        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/teams', $this->token($admin), [
            'name' => 'Doubles',
            'playerIds' => [$placeholder->getId(), $teammate->getId()],
        ]);
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $members = $this->payload($client)['members'];
        self::assertIsArray($members);
        self::assertEmpty(array_filter(
            $members,
            static fn (mixed $member): bool => is_array($member) && $member['userId'] === $latecomer->getId(),
        ));

        $client->request('GET', '/api/tournaments', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($latecomer)]);
        self::assertSame([], $this->payload($client));

        $this->json($client, 'POST', '/api/account/player/link', $this->token($latecomer), [
            'playerId' => $placeholder->getId(),
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        $client->request('GET', '/api/tournaments', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($latecomer)]);
        $listed = $this->payload($client);
        self::assertCount(1, $listed);
        self::assertSame($tournamentId, $listed[0]['id']);

        $client->request('GET', '/api/tournaments/'.$tournamentId, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$this->token($latecomer)]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
    }

    public function testChampionshipGenerationIsUniqueAndIdempotent(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-days-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-days-'.$suffix, UserRole::SIMPLE_PLAYER);
        $playerUser = $this->user('player-days-'.$suffix, UserRole::SIMPLE_PLAYER);
        $otherMember = $this->user('other-days-'.$suffix, UserRole::SIMPLE_PLAYER);
        $linked = new Player('Ines', 'Roy', 'Ines'.$suffix);
        $linked->setUser($playerUser);
        $this->em()->persist($linked);
        $players = [$linked];
        for ($index = 0; $index < 3; ++$index) {
            $players[] = new Player('P'.$index, 'Nom', 'P'.$index.$suffix);
            $this->em()->persist($players[$index + 1]);
        }
        $this->em()->flush();
        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 2, [
            'numberOfLanes' => 3,
            'excludedLaneNumbers' => [2],
            'pointsForWin' => 1,
            'pointsForDraw' => 0,
            'drawsAllowed' => false,
        ]);
        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/members', $this->token($admin), [
            'userId' => $playerUser->getId(),
        ]);
        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/members', $this->token($admin), [
            'userId' => $otherMember->getId(),
        ]);
        $teamIds = [];
        foreach ($players as $index => $player) {
            $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/teams', $this->token($admin), [
                'name' => 'Equipe '.$index,
                'playerIds' => [$player->getId()],
            ]);
            self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
            $teamIds[] = $this->payload($client)['teams'][$index]['id'];
        }

        $pairs = [];
        $dayIds = [];
        for ($dayNumber = 1; $dayNumber <= 3; ++$dayNumber) {
            $before = $this->dayCount($client, $admin, $tournamentId);
            $day = $this->generate($client, $admin, $tournamentId, ['excludedLaneNumbers' => [2]]);
            self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
            self::assertSame($before + 1, $this->dayCount($client, $admin, $tournamentId));
            self::assertCount(2, $day['matches']);
            $usedTeams = [];
            foreach ($day['matches'] as $match) {
                self::assertContains($match['laneNumber'], [1, 3, 4]);
                self::assertNotSame(2, $match['laneNumber']);
                $pair = $this->pairKey($match['team1']['teamId'], $match['team2']['teamId']);
                self::assertArrayNotHasKey($pair, $pairs);
                $pairs[$pair] = true;
                self::assertArrayNotHasKey($match['team1']['teamId'], $usedTeams);
                self::assertArrayNotHasKey($match['team2']['teamId'], $usedTeams);
                $usedTeams[$match['team1']['teamId']] = true;
                $usedTeams[$match['team2']['teamId']] = true;
            }
            $dayIds[] = $day['id'];
        }
        self::assertCount(6, $pairs);
        $this->generate($client, $admin, $tournamentId, []);
        self::assertSame(409, $client->getResponse()->getStatusCode());
        self::assertSame('no_valid_pairing', $this->payload($client)['error']);
        self::assertSame(3, $this->dayCount($client, $admin, $tournamentId));

        $detail = $this->show($client, $admin, $tournamentId);
        $firstMatch = $detail['days'][0]['matches'][0];
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$firstMatch['id'].'/score', $this->token($otherMember), [
            'score1' => 13,
            'score2' => 0,
        ]);
        self::assertSame(403, $client->getResponse()->getStatusCode());
        $playerView = $this->show($client, $playerUser, $tournamentId);
        $ownMatch = $this->matchForTeam($playerView['days'][0]['matches'], $teamIds[0]);
        self::assertTrue($ownMatch['highlighted']);
        self::assertSame($ownMatch['id'], $playerView['days'][0]['matches'][0]['id']);
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$ownMatch['id'].'/score', $this->token($playerUser), [
            'score1' => 14,
            'score2' => 0,
        ]);
        self::assertSame(400, $client->getResponse()->getStatusCode());
        self::assertArrayHasKey('score1', $this->payload($client)['errors']);
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$ownMatch['id'].'/score', $this->token($playerUser), [
            'score1' => 8,
            'score2' => 3,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$ownMatch['id'].'/score', $this->token($playerUser), [
            'score1' => 10,
            'score2' => 10,
        ]);
        self::assertSame(422, $client->getResponse()->getStatusCode());
        self::assertSame('draws_not_allowed', $this->payload($client)['error']);
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$ownMatch['id'].'/score', $this->token($admin), [
            'score1' => 13,
            'score2' => 8,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/days/'.$dayIds[0].'/close', $this->token($admin));
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertSame('closed', $this->payload($client)['status']);
        $closedView = $this->show($client, $playerUser, $tournamentId);
        $closedMatch = $this->matchForTeam($closedView['days'][0]['matches'], $teamIds[0]);
        self::assertFalse($closedMatch['viewerCanEditScore']);
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$closedMatch['id'].'/score', $this->token($playerUser), [
            'score1' => 13,
            'score2' => 1,
        ]);
        self::assertSame(403, $client->getResponse()->getStatusCode());
        self::assertSame('day_closed', $this->payload($client)['error']);
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$closedMatch['id'].'/score', $this->token($admin), [
            'score1' => 13,
            'score2' => 2,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        $secondDay = $this->show($client, $admin, $tournamentId)['days'][1];
        $replacement = $this->show($client, $admin, $tournamentId)['days'][2]['matches'][0];
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$secondDay['matches'][0]['id'].'/teams', $this->token($admin), [
            'team1Id' => $replacement['team1']['teamId'],
            'team2Id' => $replacement['team2']['teamId'],
        ]);
        self::assertSame(409, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertSame('duplicate_pairing', $this->payload($client)['error']);
        self::assertSame(
            $secondDay['matches'][0]['team1']['teamId'],
            $this->show($client, $admin, $tournamentId)['days'][1]['matches'][0]['team1']['teamId'],
        );

        $usedLanes = array_map(static fn (array $match): int => $match['laneNumber'], $secondDay['matches']);
        $freeLane = array_values(array_diff([1, 3, 4], $usedLanes))[0];
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$secondDay['matches'][0]['id'].'/lane', $this->token($admin), [
            'laneNumber' => 2,
        ]);
        self::assertSame(422, $client->getResponse()->getStatusCode());
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$secondDay['matches'][0]['id'].'/lane', $this->token($admin), [
            'laneNumber' => $freeLane,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertSame($freeLane, $this->payload($client)['laneNumber']);
    }

    public function testScoreCanBeClearedOnAChampionshipMatch(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-clear-score-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-clear-score-'.$suffix, UserRole::SIMPLE_PLAYER);
        $players = [];
        for ($index = 0; $index < 2; ++$index) {
            $players[] = new Player('C'.$index, 'Nom', 'C'.$index.$suffix);
            $this->em()->persist($players[$index]);
        }
        $this->em()->flush();
        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 2, ['numberOfLanes' => 2]);
        foreach ($players as $index => $player) {
            $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/teams', $this->token($admin), [
                'name' => 'Equipe '.$index,
                'playerIds' => [$player->getId()],
            ]);
        }
        $day = $this->generate($client, $admin, $tournamentId, []);
        $match = $day['matches'][0];
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$match['id'].'/score', $this->token($admin), [
            'score1' => 13,
            'score2' => 7,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertSame('finished', $this->payload($client)['status']);

        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$match['id'].'/score', $this->token($admin), [
            'clear' => true,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $cleared = $this->payload($client);
        self::assertNull($cleared['score1']);
        self::assertNull($cleared['score2']);
        self::assertSame('scheduled', $cleared['status']);
        self::assertSame('generated', $this->show($client, $admin, $tournamentId)['days'][0]['status']);
    }

    public function testAdminCanDeleteAnUnstartedChampionshipDay(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-delete-day-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-delete-day-'.$suffix, UserRole::SIMPLE_PLAYER);
        $players = [];
        for ($index = 0; $index < 2; ++$index) {
            $players[] = new Player('D'.$index, 'Nom', 'D'.$index.$suffix);
            $this->em()->persist($players[$index]);
        }
        $this->em()->flush();
        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 2, ['numberOfLanes' => 2]);
        foreach ($players as $index => $player) {
            $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/teams', $this->token($admin), [
                'name' => 'Equipe '.$index,
                'playerIds' => [$player->getId()],
            ]);
        }
        $day = $this->generate($client, $admin, $tournamentId, []);
        self::assertTrue($day['deletable']);

        $this->json($client, 'DELETE', '/api/tournaments/'.$tournamentId.'/days/'.$day['id'], $this->token($admin));
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertSame([], array_values(array_filter(
            $this->payload($client)['days'],
            static fn (array $entry): bool => $entry['phase'] === 'championship',
        )));

        $regenerated = $this->generate($client, $admin, $tournamentId, []);
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertSame(1, $regenerated['number']);

        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$regenerated['matches'][0]['id'].'/score', $this->token($admin), [
            'score1' => 13,
            'score2' => 5,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        $this->json($client, 'DELETE', '/api/tournaments/'.$tournamentId.'/days/'.$regenerated['id'], $this->token($admin));
        self::assertSame(409, $client->getResponse()->getStatusCode());
        self::assertSame('day_already_started', $this->payload($client)['error']);
    }

    public function testFictionalMatchesDoNotAffectTheRankingAndADayCanCloseWithoutThem(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-fake-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-fake-'.$suffix, UserRole::SIMPLE_PLAYER);
        $players = [];
        for ($index = 0; $index < 2; ++$index) {
            $players[] = new Player('F'.$index, 'Nom', 'F'.$index.$suffix);
            $this->em()->persist($players[$index]);
        }
        $this->em()->flush();
        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 2, ['numberOfLanes' => 2]);
        foreach ($players as $index => $player) {
            $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/teams', $this->token($admin), [
                'name' => 'Reelle '.$index,
                'playerIds' => [$player->getId()],
            ]);
        }
        $first = $this->generate($client, $admin, $tournamentId, []);
        $realMatch = $first['matches'][0];
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$realMatch['id'].'/score', $this->token($admin), [
            'score1' => 13,
            'score2' => 8,
        ]);
        $rankingBefore = $this->show($client, $admin, $tournamentId)['ranking'];
        $second = $this->generate($client, $admin, $tournamentId, ['fakeTeamNames' => ['Amical']]);
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertTrue($second['matches'][0]['fictional']);
        self::assertTrue($second['readyToClose']);
        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/days/'.$second['id'].'/close', $this->token($admin));
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$second['matches'][0]['id'].'/score', $this->token($admin), [
            'score1' => 13,
            'score2' => 0,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertSame($rankingBefore, $this->show($client, $admin, $tournamentId)['ranking']);
    }

    public function testPlayoffsFollowTheBracketAndDoNotChangeTheChampionshipRanking(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-ko-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-ko-'.$suffix, UserRole::SIMPLE_PLAYER);
        $players = [];
        for ($index = 0; $index < 8; ++$index) {
            $players[] = new Player('K'.$index, 'Nom', 'K'.$index.$suffix);
            $this->em()->persist($players[$index]);
        }
        $this->em()->flush();
        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 1, ['numberOfLanes' => 4]);
        $teamIds = [];
        foreach ($players as $index => $player) {
            $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/teams', $this->token($admin), [
                'name' => 'KO '.$index,
                'playerIds' => [$player->getId()],
            ]);
            $teamIds[] = $this->payload($client)['teams'][$index]['id'];
        }
        sort($teamIds);
        for ($round = 0; $round < 7; ++$round) {
            $day = $this->generate($client, $admin, $tournamentId, []);
            self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
            foreach ($day['matches'] as $match) {
                $score1 = $match['team1']['teamId'] < $match['team2']['teamId'] ? 13 : 0;
                $score2 = 13 - $score1;
                $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$match['id'].'/score', $this->token($admin), [
                    'score1' => $score1,
                    'score2' => $score2,
                ]);
                self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
            }
            $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/days/'.$day['id'].'/close', $this->token($admin));
            self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        }
        $ranking = $this->show($client, $admin, $tournamentId)['ranking'];
        self::assertSame($teamIds, array_map(static fn (array $row): int => $row['teamId'], $ranking['rows']));

        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/playoffs', $this->token($admin));
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $quarters = array_values(array_filter(
            $this->payload($client)['days'],
            static fn (array $day): bool => ($day['playoffRound'] ?? null) === 'quarter',
        ));
        self::assertCount(1, $quarters);
        $pairs = [];
        foreach ($quarters[0]['matches'] as $match) {
            $ids = [$match['team1']['teamId'], $match['team2']['teamId']];
            sort($ids);
            $pairs[] = $ids[0].'-'.$ids[1];
        }
        sort($pairs);
        $expected = [
            $teamIds[0].'-'.$teamIds[7],
            $teamIds[1].'-'.$teamIds[6],
            $teamIds[2].'-'.$teamIds[5],
            $teamIds[3].'-'.$teamIds[4],
        ];
        sort($expected);
        self::assertSame($expected, $pairs);

        foreach ($quarters[0]['matches'] as $match) {
            $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$match['id'].'/score', $this->token($admin), [
                'score1' => 13,
                'score2' => 7,
            ]);
        }
        $semis = array_values(array_filter(
            $this->show($client, $admin, $tournamentId)['days'],
            static fn (array $day): bool => ($day['playoffRound'] ?? null) === 'semi',
        ));
        foreach ($semis[0]['matches'] as $match) {
            self::assertNotNull($match['team1']['teamId']);
            self::assertNotNull($match['team2']['teamId']);
            $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$match['id'].'/score', $this->token($admin), [
                'score1' => 13,
                'score2' => 9,
            ]);
        }
        $finalDay = array_values(array_filter(
            $this->show($client, $admin, $tournamentId)['days'],
            static fn (array $day): bool => ($day['playoffRound'] ?? null) === 'final',
        ))[0];
        self::assertNotNull($finalDay['matches'][0]['team1']['teamId']);
        self::assertNotNull($finalDay['matches'][0]['team2']['teamId']);
        self::assertSame($ranking, $this->show($client, $admin, $tournamentId)['ranking']);
    }

    public function testPlayoffsCanStartWithFewerThanEightTeamsAndIncompleteChampionshipDays(): void
    {
        $client = $this->createDatabaseClient();
        $suffix = $this->suffix();
        $master = $this->user('master-six-'.$suffix, UserRole::MASTER);
        $admin = $this->user('admin-six-'.$suffix, UserRole::SIMPLE_PLAYER);
        $players = [];
        for ($index = 0; $index < 6; ++$index) {
            $players[] = new Player('S'.$index, 'Nom', 'S'.$index.$suffix);
            $this->em()->persist($players[$index]);
        }
        $this->em()->flush();
        $tournamentId = $this->createTournament($client, $master, [$admin->getId()], 1, 1, ['numberOfLanes' => 2]);
        $teamIds = [];
        foreach ($players as $index => $player) {
            $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/teams', $this->token($admin), [
                'name' => 'Six '.$index,
                'playerIds' => [$player->getId()],
            ]);
            $teamIds[] = $this->payload($client)['teams'][$index]['id'];
        }
        $day = $this->generate($client, $admin, $tournamentId, []);
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $scored = $day['matches'][0];
        $this->json($client, 'PATCH', '/api/tournaments/'.$tournamentId.'/matches/'.$scored['id'].'/score', $this->token($admin), [
            'score1' => 13,
            'score2' => 5,
        ]);
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        self::assertTrue($day['readyToClose']);
        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/days/'.$day['id'].'/close', $this->token($admin));
        self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        $detail = $this->show($client, $admin, $tournamentId);
        self::assertCount(6, array_filter(
            $detail['ranking']['rows'],
            static fn (array $row): bool => $row['qualifiedForPlayoffs'],
        ));

        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/playoffs', $this->token($admin));
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
        $payload = $this->payload($client);
        $quarters = array_values(array_filter(
            $payload['days'],
            static fn (array $item): bool => ($item['playoffRound'] ?? null) === 'quarter',
        ));
        self::assertCount(1, $quarters);
        self::assertCount(2, $quarters[0]['matches']);
        $pairs = [];
        foreach ($quarters[0]['matches'] as $match) {
            $ids = [$match['team1']['teamId'], $match['team2']['teamId']];
            sort($ids);
            $pairs[] = $ids[0].'-'.$ids[1];
        }
        sort($pairs);
        $rankedTeamIds = array_map(
            static fn (array $row): int => $row['teamId'],
            $detail['ranking']['rows'],
        );
        $expected = [
            $rankedTeamIds[2].'-'.$rankedTeamIds[5],
            $rankedTeamIds[3].'-'.$rankedTeamIds[4],
        ];
        sort($expected);
        self::assertSame($expected, $pairs);

        $semis = array_values(array_filter(
            $payload['days'],
            static fn (array $item): bool => ($item['playoffRound'] ?? null) === 'semi',
        ));
        self::assertCount(1, $semis);
        $semiTeams = [];
        foreach ($semis[0]['matches'] as $match) {
            if ($match['team1']['teamId'] !== null) {
                $semiTeams[] = $match['team1']['teamId'];
            }
            if ($match['team2']['teamId'] !== null) {
                $semiTeams[] = $match['team2']['teamId'];
            }
        }
        sort($semiTeams);
        self::assertSame([$rankedTeamIds[0], $rankedTeamIds[1]], $semiTeams);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function settings(array $administratorIds, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Coupe club',
            'description' => 'Aller simple',
            'administratorUserIds' => $administratorIds,
            'minPlayersPerTeam' => 1,
            'maxPlayersPerTeam' => 2,
            'pointsForWin' => 1,
            'pointsForDraw' => 0,
            'drawsAllowed' => false,
            'numberOfLanes' => 4,
            'excludedLaneNumbers' => [],
        ], $overrides);
    }

    /**
     * @param list<int> $administratorIds
     * @param array<string, mixed> $overrides
     */
    private function createTournament(KernelBrowser $client, User $master, array $administratorIds, int $min, int $max, array $overrides = []): int
    {
        $this->json($client, 'POST', '/api/tournaments', $this->token($master), $this->settings($administratorIds, array_merge([
            'minPlayersPerTeam' => $min,
            'maxPlayersPerTeam' => $max,
        ], $overrides)));
        self::assertSame(201, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());

        return $this->payload($client)['id'];
    }

    /**
     * @param list<int> $absentTeamIds
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function generate(KernelBrowser $client, User $admin, int $tournamentId, array $extra, array $absentTeamIds = []): array
    {
        $this->json($client, 'POST', '/api/tournaments/'.$tournamentId.'/days', $this->token($admin), array_merge([
            'absentTeamIds' => $absentTeamIds,
            'fakeTeamNames' => [],
            'excludedLaneNumbers' => [],
        ], $extra));

        return $this->payload($client);
    }

    /**
     * @return array<string, mixed>
     */
    private function show(KernelBrowser $client, User $user, int $tournamentId): array
    {
        $client->request('GET', '/api/tournaments/'.$tournamentId, server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->token($user),
        ]);

        return $this->payload($client);
    }

    private function dayCount(KernelBrowser $client, User $user, int $tournamentId): int
    {
        return count($this->show($client, $user, $tournamentId)['days']);
    }

    /**
     * @param list<array<string, mixed>> $matches
     * @return array<string, mixed>
     */
    private function matchForTeam(array $matches, int $teamId): array
    {
        foreach ($matches as $match) {
            if ($match['team1']['teamId'] === $teamId || $match['team2']['teamId'] === $teamId) {
                return $match;
            }
        }
        self::fail('Team match was not found.');
    }

    private function pairKey(int $left, int $right): string
    {
        return min($left, $right).'-'.max($left, $right);
    }

    private function user(string $prefix, UserRole $role): User
    {
        $user = new User($prefix.'@test.local');
        $user->setPassword($this->hasher()->hashPassword($user, 'password123'));
        $user->setRole($role);
        $this->em()->persist($user);

        return $user;
    }

    private function token(User $user): string
    {
        return $this->jwt()->encode([
            'username' => $user->getEmail(),
            'sub' => (string) $user->getId(),
        ]);
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function json(KernelBrowser $client, string $method, string $uri, string $token, ?array $body = null): void
    {
        $server = [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
        $client->request($method, $uri, server: $server, content: $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(KernelBrowser $client): array
    {
        $decoded = json_decode((string) $client->getResponse()->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function hasher(): UserPasswordHasherInterface
    {
        return static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    private function jwt(): JWTEncoderInterface
    {
        return static::getContainer()->get(JWTEncoderInterface::class);
    }

    private function suffix(): string
    {
        return bin2hex(random_bytes(4));
    }
}
