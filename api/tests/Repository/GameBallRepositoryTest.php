<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Dto\Request\CompleteMatchEndBallDto;
use App\Entity\Game;
use App\Repository\GameBallRepository;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use App\Tests\Support\MatchTestHelpers;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GameBallRepositoryTest extends KernelTestCase
{
    use MatchTestHelpers;

    private GameBallRepository $balls;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->matchService = $container->get(MatchService::class);
        $this->recording = $container->get(MatchRecordingService::class);
        $this->jwtEncoder = $container->get(JWTEncoderInterface::class);
        $this->balls = $container->get(GameBallRepository::class);
    }

    public function testAggregateByGameExcludesBallsFromCanceledEnds(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $req->ends[0]->canceled = true;
        $req->ends[0]->points = 0;

        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [-2];
        $ball->shotTypes = ['point'];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        /** @var Game|null $game */
        $game = $this->em->getRepository(Game::class)->find($matchId);
        self::assertNotNull($game);

        $agg = $this->balls->aggregateByGame($game);

        self::assertSame([], $agg);
    }

    public function testAggregateByGamePerShotExcludesBallsFromCanceledEnds(): void
    {
        [$matchId, $playerAId, $playerBId] = $this->createHeadToHead();

        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $req->ends[0]->canceled = true;
        $req->ends[0]->points = 0;

        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1];
        $ball->shotTypes = ['tir'];
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);

        /** @var Game|null $game */
        $game = $this->em->getRepository(Game::class)->find($matchId);
        self::assertNotNull($game);

        $agg = $this->balls->aggregateByGamePerShot($game);

        self::assertSame([], $agg);
    }
}
