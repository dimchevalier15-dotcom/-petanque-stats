<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Request\CompleteMatchEndBallDto;
use App\Dto\Request\CompleteMatchEndDto;
use App\Dto\Request\CreateMatchRequest;
use App\Entity\Competition;
use App\Entity\Player;
use App\Entity\User;
use App\Enum\DistanceBucket;
use App\Enum\GameType;
use App\Enum\MatchNature;
use App\Service\MatchHistoryService;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use App\Service\PlayerStatsService;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PlayerStatsServiceTest extends KernelTestCase
{
    use MatchTestHelpers;

    private PlayerStatsService $stats;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
        $this->stats = $container->get(PlayerStatsService::class);
    }

    public function testStatsReturnsNoPlayerWhenAccountHasNoLinkedPlayer(): void
    {
        $email = sprintf('no-player-%s@test.local', bin2hex(random_bytes(4)));
        $user = new User($email);
        $user->setPassword('hash');
        $this->em->persist($user);
        $this->em->flush();

        $token = $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);

        $res = $this->stats->statsForToken($token);

        self::assertSame('no_player', $res->status);
        self::assertNull($res->playerId);
    }

    public function testStatsComputesVictoriesAndOverallAverage(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 5, ballNote: 2);
        $this->createAndCompleteMatch($playerId, $opponentId, winner: 'B', points: 3, ballNote: 0);

        $res = $this->stats->statsForToken($token);

        self::assertSame('ok', $res->status);
        self::assertSame(2, $res->summary->matchesPlayed);
        self::assertSame(1, $res->summary->victories);
        self::assertSame(1, $res->summary->defeats);
        self::assertSame(50.0, $res->summary->winRate);
        self::assertNotNull($res->overall);
        self::assertSame(1.0, $res->overall->average);
        self::assertSame(50.0, $res->overall->successRate);
        self::assertNotNull($res->point);
        self::assertSame(50.0, $res->point->successRate);
        self::assertNull($res->tir);
        self::assertSame(2, $res->summary->totalBalls);
        self::assertCount(2, $res->evolution);
    }

    public function testStatsReturnsNoTrackedDataWhenEndsExistWithoutBalls(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 1, ballNote: null);

        $res = $this->stats->statsForToken($token);

        self::assertSame('no_tracked_data', $res->status);
        self::assertSame(1, $res->summary->matchesPlayed);
        self::assertSame(0, $res->summary->totalBalls);
    }

    public function testStatsGroupsByMatchNature(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $trainingId = $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 2, ballNote: 1);
        $this->setMatchNature($trainingId, MatchNature::TRAINING);

        $competitionId = $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 1, ballNote: 2);
        $this->setMatchNature($competitionId, MatchNature::COMPETITION);

        $res = $this->stats->statsForToken($token);

        self::assertSame('ok', $res->status);
        self::assertCount(2, $res->byNature);
        $natures = array_map(static fn ($row) => $row->nature, $res->byNature);
        self::assertContains('training', $natures);
        self::assertContains('competition', $natures);
    }

    public function testStatsGroupsByFormat(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 2, ballNote: 1, type: 'tete_a_tete');
        $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 1, ballNote: 2, type: 'tete_a_tete');

        $res = $this->stats->statsForToken($token);

        self::assertSame('ok', $res->status);
        self::assertCount(1, $res->byFormat);
        self::assertSame('tete_a_tete', $res->byFormat[0]->type);
        self::assertSame(2, $res->byFormat[0]->matchCount);
        self::assertSame(2, $res->byFormat[0]->victories);
    }

    public function testStatsGroupsByDistanceBucket(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $this->createAndCompleteMatch(
            $playerId,
            $opponentId,
            winner: 'A',
            points: 2,
            ballNote: 1,
            distances: [6.5],
        );
        $this->createAndCompleteMatch(
            $playerId,
            $opponentId,
            winner: 'A',
            points: 1,
            ballNote: 2,
            distances: [10.5],
        );

        $res = $this->stats->statsForToken($token);

        self::assertSame('ok', $res->status);
        self::assertGreaterThanOrEqual(2, count($res->byDistance));
        $buckets = array_map(static fn ($row) => $row->bucket, $res->byDistance);
        self::assertContains('6_7', $buckets);
        self::assertContains('10_plus', $buckets);
    }

    public function testStatsFiltersByFormat(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 2, ballNote: 1, type: 'tete_a_tete');
        $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 1, ballNote: 2, type: 'tete_a_tete');

        $res = $this->stats->statsForToken($token, null, null, GameType::DOUBLETTE);

        self::assertSame('no_data_in_period', $res->status);
    }

    public function testStatsFiltersByDistanceBucket(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $this->createAndCompleteMatch(
            $playerId,
            $opponentId,
            winner: 'A',
            points: 2,
            ballNote: 1,
            distances: [6.5],
        );
        $this->createAndCompleteMatch(
            $playerId,
            $opponentId,
            winner: 'A',
            points: 1,
            ballNote: 2,
            distances: [10.5],
        );

        $res = $this->stats->statsForToken($token, null, null, null, DistanceBucket::FROM_6_TO_7);

        self::assertSame('ok', $res->status);
        self::assertSame(1, $res->summary->totalBalls);
        self::assertSame(1.0, $res->overall?->average);
        self::assertSame([], $res->byDistance);
    }

    public function testStatsFiltersByCompetition(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $openA = new Competition('Open A', new \DateTimeImmutable('2026-05-10'), 'France');
        $openB = new Competition('Open B', new \DateTimeImmutable('2026-06-10'), 'France');
        $this->em->persist($openA);
        $this->em->persist($openB);
        $this->em->flush();

        $matchA = $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 2, ballNote: 2);
        $this->setMatchCompetition($matchA, MatchNature::COMPETITION, $openA);

        $matchB = $this->createAndCompleteMatch($playerId, $opponentId, winner: 'A', points: 1, ballNote: 0);
        $this->setMatchCompetition($matchB, MatchNature::COMPETITION, $openB);

        $res = $this->stats->statsForToken($token, MatchNature::COMPETITION, null, null, null, (int) $openA->getId());

        self::assertSame('ok', $res->status);
        self::assertSame(1, $res->summary->matchesPlayed);
        self::assertSame(1, $res->summary->totalBalls);
        self::assertSame(2.0, $res->overall?->average);
    }

    public function testStatsSuccessRateSplitsPointAndTir(): void
    {
        [$token, $player, $opponentId] = $this->createLinkedPlayerWithOpponent();
        $playerId = (int) $player->getId();

        $suffix = bin2hex(random_bytes(4));
        $owner = new User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerId];
        $createReq->teamB = [$opponentId];
        $createReq->trackedPlayers = [$playerId, $opponentId];
        $matchId = $this->matchService->create($createReq, $owner)->id;

        $req = $this->baseCompleteRequest($playerId, $opponentId);
        $req->ends = [
            $this->scoredEnd(1, $playerId, [2, 1, 1], ['point', 'point', 'point']),
            $this->scoredEnd(2, $playerId, [0, -1], ['point', 'point']),
            $this->scoredEnd(3, $playerId, [2, 2, 1], ['tir', 'tir', 'tir']),
            $this->scoredEnd(4, $playerId, [0, -2], ['tir', 'tir']),
        ];
        $this->recording->complete($matchId, $req);

        $res = $this->stats->statsForToken($token);

        self::assertSame('ok', $res->status);
        self::assertNotNull($res->point);
        self::assertSame(60.0, $res->point->successRate);
        self::assertNotNull($res->tir);
        self::assertSame(60.0, $res->tir->successRate);
    }

    private function createAndCompleteMatch(
        int $playerAId,
        int $opponentId,
        string $winner,
        int $points,
        ?int $ballNote,
        string $type = 'tete_a_tete',
        array $distances = [],
    ): int {
        $suffix = bin2hex(random_bytes(4));
        $owner = new User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $createReq = new CreateMatchRequest();
        $createReq->type = $type;
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerAId];
        $createReq->teamB = [$opponentId];
        $createReq->trackedPlayers = [$playerAId, $opponentId];

        $matchId = $this->matchService->create($createReq, $owner)->id;

        $req = $this->baseCompleteRequest($playerAId, $opponentId);
        $req->ends[0]->winner = $winner;
        $req->ends[0]->points = $points;

        if ($ballNote !== null) {
            $ball = new CompleteMatchEndBallDto();
            $ball->playerId = $playerAId;
            $ball->notes = [$ballNote];
            $ball->shotTypes = ['point'];
            $ball->distances = $distances !== [] ? $distances : [null];
            $req->ends[0]->balls = [$ball];
        }

        $this->recording->complete($matchId, $req);

        return $matchId;
    }

    private function setMatchCompetition(int $matchId, MatchNature $nature, Competition $competition): void
    {
        $game = $this->em->getRepository(\App\Entity\Game::class)->find($matchId);
        self::assertNotNull($game);
        $game->setNature($nature);
        $game->setCompetition($competition);
        $this->em->flush();
    }
}
