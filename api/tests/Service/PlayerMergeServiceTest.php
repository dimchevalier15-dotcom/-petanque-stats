<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\GameParticipant;
use App\Entity\LiveMatch;
use App\Entity\Player;
use App\Entity\SelectionGlobale;
use App\Entity\SelectionGlobalePlayer;
use App\Entity\ShootingSession;
use App\Entity\User;
use App\Enum\GameType;
use App\Service\PlayerMergeException;
use App\Service\PlayerMergeService;
use App\Tests\Support\KernelDatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PlayerMergeServiceTest extends KernelDatabaseTestCase
{
    private EntityManagerInterface $em;
    private PlayerMergeService $merge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->merge = static::getContainer()->get(PlayerMergeService::class);
    }

    public function testMergeMovesHistoryAndKeepsTheOnlyLinkedUser(): void
    {
        $suffix = bin2hex(random_bytes(3));
        $user = $this->user('keep-'.$suffix);
        $keep = new Player('Jean', 'Garde', 'JG');
        $drop = new Player('Jean', 'Doublon', 'JD');
        $drop->setUser($user);
        $this->em->persist($keep);
        $this->em->persist($drop);
        $this->em->flush();

        $session = new ShootingSession($drop);
        $selection = new SelectionGlobale('Sel '.$suffix);
        $this->em->persist($session);
        $this->em->persist($selection);
        $this->em->flush();
        $this->em->persist(new SelectionGlobalePlayer($selection, $drop));
        $this->em->persist(new SelectionGlobalePlayer($selection, $keep));

        $live = new LiveMatch('11111111-1111-1111-1111-'.substr(md5($suffix), 0, 12), [
            'teamA' => [(int) $drop->getId()],
            'teamB' => [424242],
            'trackedPlayers' => [(int) $drop->getId()],
            'playerNames' => [(int) $drop->getId() => 'Doublon'],
            'ends' => [[
                'shots' => [['playerId' => (int) $drop->getId(), 'note' => 1]],
                'roles' => [(int) $drop->getId() => 'tireur'],
            ]],
            'substitutions' => [[
                'outPlayerId' => (int) $drop->getId(),
                'inPlayerId' => 7,
            ]],
        ]);
        $this->em->persist($live);
        $this->em->flush();

        $keepId = (int) $keep->getId();
        $dropId = (int) $drop->getId();
        $sessionId = (int) $session->getId();
        $userId = (int) $user->getId();
        $liveId = (int) $live->getId();

        $result = $this->merge->merge($keepId, $dropId, null);

        self::assertSame($keepId, $result->playerId);
        self::assertSame($userId, $result->userId);
        $this->em->clear();

        self::assertNull($this->em->find(Player::class, $dropId));
        $kept = $this->em->find(Player::class, $keepId);
        self::assertNotNull($kept);
        self::assertSame($userId, $kept->getUser()?->getId());

        $moved = $this->em->find(ShootingSession::class, $sessionId);
        self::assertNotNull($moved);
        self::assertTrue($moved->belongsTo($kept));

        $memberships = (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM selection_globale_players WHERE player_id = ?',
            [$keepId],
        );
        self::assertSame(1, $memberships);

        $data = json_decode((string) $this->em->getConnection()->fetchOne(
            'SELECT data FROM live_matches WHERE id = ?',
            [$liveId],
        ), true);
        self::assertSame([$keepId], $data['teamA']);
        self::assertSame([$keepId], $data['trackedPlayers']);
        self::assertArrayHasKey((string) $keepId, $data['playerNames']);
        self::assertSame($keepId, $data['ends'][0]['shots'][0]['playerId']);
        self::assertSame($keepId, $data['substitutions'][0]['outPlayerId']);
    }

    public function testMergeRequiresAChoiceWhenBothPlayersHaveAUser(): void
    {
        $suffix = bin2hex(random_bytes(3));
        $keepUser = $this->user('keep-user-'.$suffix);
        $dropUser = $this->user('drop-user-'.$suffix);
        $keep = new Player('A', 'Un', 'A1');
        $keep->setUser($keepUser);
        $drop = new Player('A', 'Deux', 'A2');
        $drop->setUser($dropUser);
        $this->em->persist($keep);
        $this->em->persist($drop);
        $this->em->flush();

        $keepId = (int) $keep->getId();
        $dropId = (int) $drop->getId();
        $dropUserId = (int) $dropUser->getId();

        try {
            $this->merge->merge($keepId, $dropId, null);
            self::fail('Expected a user choice.');
        } catch (PlayerMergeException $e) {
            self::assertSame(PlayerMergeException::USER_CHOICE_REQUIRED, $e->errorCode);
        }

        $this->merge->merge($keepId, $dropId, $dropUserId);
        $this->em->clear();

        $kept = $this->em->find(Player::class, $keepId);
        self::assertNotNull($kept);
        self::assertSame($dropUserId, $kept->getUser()?->getId());
        self::assertNotNull($this->em->find(User::class, (int) $keepUser->getId()));
    }

    public function testMergeRefusesPlayersWhoShareAMatch(): void
    {
        $keep = new Player('B', 'Un', 'B1');
        $drop = new Player('B', 'Deux', 'B2');
        $game = new Game(GameType::TETE_A_TETE);
        $this->em->persist($keep);
        $this->em->persist($drop);
        $this->em->persist($game);
        $this->em->flush();
        $this->em->persist(new GameParticipant($game, $keep, 'A', 1));
        $this->em->persist(new GameParticipant($game, $drop, 'B', 1));
        $this->em->flush();

        $this->expectException(PlayerMergeException::class);
        $this->merge->merge((int) $keep->getId(), (int) $drop->getId(), null);
    }

    private function user(string $local): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User($local.'@test.local');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $this->em->persist($user);

        return $user;
    }
}
