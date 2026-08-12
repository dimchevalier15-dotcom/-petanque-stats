<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Dto\Request\CompleteMatchEndBallDto;
use App\Dto\Request\CompleteMatchEndDto;
use App\Dto\Request\CompleteMatchRequest;
use App\Dto\Request\CreateMatchRequest;
use App\Entity\Player;
use App\Entity\User;
use App\Enum\MatchNature;
use App\Service\MatchRecordingService;
use App\Service\MatchService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

trait MatchTestHelpers
{
    protected EntityManagerInterface $em;
    protected MatchService $matchService;
    protected MatchRecordingService $recording;
    protected JWTEncoderInterface $jwtEncoder;

    /**
     * @return array{0:int,1:int,2:int} matchId, playerAId, playerBId
     */
    protected function createHeadToHead(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $owner = new User('owner'.$suffix.'@test.local');
        $owner->setPassword('hash');
        $this->em->persist($owner);

        $playerA = new Player('Alice', 'Test'.$suffix, 'Ali'.$suffix);
        $playerB = new Player('Bob', 'Test'.$suffix, 'Bob'.$suffix);
        $this->em->persist($playerA);
        $this->em->persist($playerB);
        $this->em->flush();

        $createReq = new CreateMatchRequest();
        $createReq->type = 'tete_a_tete';
        $createReq->targetScore = 13;
        $createReq->statisticsMode = 'standard';
        $createReq->teamA = [$playerA->getId()];
        $createReq->teamB = [$playerB->getId()];
        $createReq->trackedPlayers = [$playerA->getId(), $playerB->getId()];

        $created = $this->matchService->create($createReq, $owner);

        return [$created->id, (int) $playerA->getId(), (int) $playerB->getId()];
    }

    /**
     * @return array{0:string,1:Player,2:int} token, linked player, opponent id
     */
    protected function createLinkedPlayerWithOpponent(): array
    {
        $suffix = bin2hex(random_bytes(4));
        $email = 'match-test-'.$suffix.'@test.local';
        $user = new User($email);
        $user->setPassword('hash');
        $this->em->persist($user);

        $player = new Player('Jean', 'Dupont'.$suffix, 'Jeannot');
        $player->setUser($user);
        $opponent = new Player('Marie', 'Martin'.$suffix, '');
        $this->em->persist($player);
        $this->em->persist($opponent);
        $this->em->flush();

        $token = $this->jwtEncoder->encode(['username' => $email, 'sub' => (string) $user->getId()]);

        return [$token, $player, (int) $opponent->getId()];
    }

    protected function completeHeadToHead(int $matchId, int $playerAId, int $playerBId, int $pointsA = 1): void
    {
        $req = $this->baseCompleteRequest($playerAId, $playerBId);
        $ball = new CompleteMatchEndBallDto();
        $ball->playerId = $playerAId;
        $ball->notes = [1];
        $ball->shotTypes = ['point'];
        $req->ends[0]->points = $pointsA;
        $req->ends[0]->balls = [$ball];

        $this->recording->complete($matchId, $req);
    }

    protected function baseCompleteRequest(int $playerAId, int $playerBId): CompleteMatchRequest
    {
        $req = new CompleteMatchRequest();
        $req->type = 'tete_a_tete';
        $req->targetScore = 13;
        $req->statisticsMode = 'standard';
        $req->teamA = [$playerAId];
        $req->teamB = [$playerBId];
        $req->trackedPlayers = [$playerAId, $playerBId];

        $end = new CompleteMatchEndDto();
        $end->index = 1;
        $end->winner = 'A';
        $end->points = 1;
        $end->canceled = false;
        $end->balls = [];
        $req->ends = [$end];

        return $req;
    }

    protected function setMatchNature(int $matchId, MatchNature $nature): void
    {
        $game = $this->em->getRepository(\App\Entity\Game::class)->find($matchId);
        self::assertNotNull($game);
        $game->setNature($nature);
        $this->em->flush();
    }
}
