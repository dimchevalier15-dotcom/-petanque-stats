<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\PlayerMergePreviewResponse;
use App\Dto\Response\PlayerMergeResultResponse;
use App\Dto\Response\PlayerMergeSideResponse;
use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class PlayerMergeService
{
    /**
     * Unique columns that identify a row already owned by the kept player.
     * The duplicate row is removed, then the remaining rows are reassigned.
     *
     * @var array<string, string>
     */
    private const DEDUPE_BY_COLUMN = [
        'match_end_player_roles' => 'end_id',
        'match_tracked_players' => 'match_id',
        'selection_globale_players' => 'selection_globale_id',
    ];

    public function __construct(
        private PlayerRepository $players,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws PlayerMergeException
     */
    public function preview(int $keepPlayerId, int $dropPlayerId): PlayerMergePreviewResponse
    {
        [$keep, $drop] = $this->loadPair($keepPlayerId, $dropPlayerId);

        return new PlayerMergePreviewResponse(
            $this->side($keep),
            $this->side($drop),
            $keep->getUser() !== null && $drop->getUser() !== null,
        );
    }

    /**
     * @throws PlayerMergeException
     */
    public function merge(int $keepPlayerId, int $dropPlayerId, ?int $keepUserId): PlayerMergeResultResponse
    {
        [$keep, $drop] = $this->loadPair($keepPlayerId, $dropPlayerId);
        $chosenUserId = $this->resolveUserId($keep, $drop, $keepUserId);
        $clubId = $keep->getClub()?->getId() ?? $drop->getClub()?->getId();

        $this->em->clear();

        $this->em->wrapInTransaction(function () use ($keepPlayerId, $dropPlayerId, $chosenUserId, $clubId): void {
            $conn = $this->em->getConnection();
            $this->assertNoSharedMatch($conn, $keepPlayerId, $dropPlayerId);
            $this->assertNoSplitTournament($conn, $keepPlayerId, $dropPlayerId);
            $this->reassignReferences($conn, $keepPlayerId, $dropPlayerId);
            $this->rewriteLiveMatches($conn, $keepPlayerId, $dropPlayerId);
            $this->assertNothingLeft($conn, $dropPlayerId);

            $conn->executeStatement(
                'UPDATE players SET user_id = NULL WHERE id IN (?, ?)',
                [$keepPlayerId, $dropPlayerId],
            );
            if ($chosenUserId !== null) {
                $conn->executeStatement(
                    'UPDATE players SET user_id = ? WHERE id = ?',
                    [$chosenUserId, $keepPlayerId],
                );
            }
            if ($clubId !== null) {
                $conn->executeStatement(
                    'UPDATE players SET club_id = ? WHERE id = ? AND club_id IS NULL',
                    [$clubId, $keepPlayerId],
                );
            }
            $conn->executeStatement('DELETE FROM players WHERE id = ?', [$dropPlayerId]);
        });

        $this->em->clear();

        return new PlayerMergeResultResponse($keepPlayerId, $chosenUserId);
    }

    /**
     * @return array{0: Player, 1: Player}
     */
    private function loadPair(int $keepPlayerId, int $dropPlayerId): array
    {
        if ($keepPlayerId === $dropPlayerId) {
            throw new PlayerMergeException(PlayerMergeException::SAME_PLAYER);
        }

        $keep = $this->players->find($keepPlayerId);
        $drop = $this->players->find($dropPlayerId);
        if (!$keep instanceof Player || !$drop instanceof Player) {
            throw new PlayerMergeException(PlayerMergeException::NOT_FOUND);
        }
        if ($keep->isPlaceholder() || $drop->isPlaceholder()) {
            throw new PlayerMergeException(PlayerMergeException::PLACEHOLDER);
        }

        return [$keep, $drop];
    }

    private function resolveUserId(Player $keep, Player $drop, ?int $keepUserId): ?int
    {
        $keepUser = $keep->getUser()?->getId();
        $dropUser = $drop->getUser()?->getId();
        $linked = array_values(array_filter([$keepUser, $dropUser], static fn (?int $id): bool => $id !== null));

        if ($linked === []) {
            return null;
        }
        if (count($linked) === 1) {
            return $linked[0];
        }
        if ($keepUserId === null) {
            throw new PlayerMergeException(PlayerMergeException::USER_CHOICE_REQUIRED);
        }
        if (!in_array($keepUserId, $linked, true)) {
            throw new PlayerMergeException(PlayerMergeException::INVALID_USER);
        }

        return $keepUserId;
    }

    private function side(Player $player): PlayerMergeSideResponse
    {
        return new PlayerMergeSideResponse(
            (int) $player->getId(),
            $player->getFirstName(),
            $player->getLastName(),
            $player->getNickname(),
            $player->getClub()?->getName(),
            $player->getUser()?->getId(),
            $player->getUser()?->getEmail(),
        );
    }

    private function assertNoSharedMatch(Connection $conn, int $keepPlayerId, int $dropPlayerId): void
    {
        $shared = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM match_players d
             INNER JOIN match_players k ON k.match_id = d.match_id AND k.player_id = ?
             WHERE d.player_id = ?',
            [$keepPlayerId, $dropPlayerId],
        );
        if ($shared > 0) {
            throw new PlayerMergeException(PlayerMergeException::SAME_MATCH);
        }
    }

    private function assertNoSplitTournament(Connection $conn, int $keepPlayerId, int $dropPlayerId): void
    {
        $split = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM tournament_team_players d
             INNER JOIN tournament_team_players k
               ON k.tournament_id = d.tournament_id AND k.player_id = ? AND k.team_id <> d.team_id
             WHERE d.player_id = ?',
            [$keepPlayerId, $dropPlayerId],
        );
        if ($split > 0) {
            throw new PlayerMergeException(PlayerMergeException::SAME_TOURNAMENT);
        }

        $conn->executeStatement(
            'DELETE d FROM tournament_team_players d
             INNER JOIN tournament_team_players k ON k.team_id = d.team_id AND k.player_id = ?
             WHERE d.player_id = ?',
            [$keepPlayerId, $dropPlayerId],
        );
    }

    private function reassignReferences(Connection $conn, int $keepPlayerId, int $dropPlayerId): void
    {
        foreach ($this->playerForeignKeys($conn) as $ref) {
            $table = $ref['table'];
            $column = $ref['column'];
            if ($table === 'tournament_team_players') {
                $conn->executeStatement(
                    sprintf('UPDATE `%s` SET `%s` = ? WHERE `%s` = ?', $table, $column, $column),
                    [$keepPlayerId, $dropPlayerId],
                );
                continue;
            }

            $scope = self::DEDUPE_BY_COLUMN[$table] ?? null;
            if ($scope !== null) {
                $conn->executeStatement(
                    sprintf(
                        'DELETE d FROM `%s` d
                         INNER JOIN `%s` k ON k.`%s` = d.`%s` AND k.`%s` = ?
                         WHERE d.`%s` = ?',
                        $table,
                        $table,
                        $scope,
                        $scope,
                        $column,
                        $column,
                    ),
                    [$keepPlayerId, $dropPlayerId],
                );
            }

            $conn->executeStatement(
                sprintf('UPDATE `%s` SET `%s` = ? WHERE `%s` = ?', $table, $column, $column),
                [$keepPlayerId, $dropPlayerId],
            );
        }
    }

    private function assertNothingLeft(Connection $conn, int $dropPlayerId): void
    {
        foreach ($this->playerForeignKeys($conn) as $ref) {
            $left = (int) $conn->fetchOne(
                sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $ref['table'], $ref['column']),
                [$dropPlayerId],
            );
            if ($left > 0) {
                throw new PlayerMergeException(PlayerMergeException::UNMOVED_REFERENCE, $ref['table']);
            }
        }
    }

    /**
     * @return list<array{table: string, column: string}>
     */
    private function playerForeignKeys(Connection $conn): array
    {
        $rows = $conn->fetchAllAssociative(
            'SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = \'players\'
               AND REFERENCED_COLUMN_NAME = \'id\'',
        );

        $refs = [];
        foreach ($rows as $row) {
            $table = (string) ($row['table_name'] ?? $row['TABLE_NAME'] ?? '');
            $column = (string) ($row['column_name'] ?? $row['COLUMN_NAME'] ?? '');
            if ($table === '' || $column === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table.$column)) {
                continue;
            }
            $refs[] = ['table' => $table, 'column' => $column];
        }

        return $refs;
    }

    private function rewriteLiveMatches(Connection $conn, int $keepPlayerId, int $dropPlayerId): void
    {
        $rows = $conn->fetchAllAssociative('SELECT id, data FROM live_matches');
        foreach ($rows as $row) {
            $raw = $row['data'] ?? null;
            $data = is_string($raw) ? json_decode($raw, true) : $raw;
            if (!is_array($data)) {
                continue;
            }
            $next = $this->rewriteLiveData($data, $keepPlayerId, $dropPlayerId);
            if ($next === $data) {
                continue;
            }
            $conn->executeStatement(
                'UPDATE live_matches SET data = ? WHERE id = ?',
                [json_encode($next, JSON_THROW_ON_ERROR), $row['id']],
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function rewriteLiveData(array $data, int $keepPlayerId, int $dropPlayerId): array
    {
        foreach (['teamA', 'teamB', 'trackedPlayers'] as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                continue;
            }
            $ids = [];
            foreach ($data[$key] as $id) {
                $resolved = (int) $id === $dropPlayerId ? $keepPlayerId : (int) $id;
                $ids[$resolved] = $resolved;
            }
            $data[$key] = array_values($ids);
        }

        foreach (['defaultShotTypes', 'startingRoles', 'currentRoles', 'playerNames', 'shortPlayerNames'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data[$key] = $this->rekeyPlayerMap($data[$key], $keepPlayerId, $dropPlayerId);
            }
        }

        if (isset($data['substitutions']) && is_array($data['substitutions'])) {
            foreach ($data['substitutions'] as $index => $sub) {
                if (!is_array($sub)) {
                    continue;
                }
                foreach (['outPlayerId', 'inPlayerId'] as $field) {
                    if (isset($sub[$field]) && (int) $sub[$field] === $dropPlayerId) {
                        $sub[$field] = $keepPlayerId;
                    }
                }
                $data['substitutions'][$index] = $sub;
            }
        }

        if (isset($data['ends']) && is_array($data['ends'])) {
            foreach ($data['ends'] as $index => $end) {
                if (!is_array($end)) {
                    continue;
                }
                if (isset($end['roles']) && is_array($end['roles'])) {
                    $end['roles'] = $this->rekeyPlayerMap($end['roles'], $keepPlayerId, $dropPlayerId);
                }
                if (isset($end['shots']) && is_array($end['shots'])) {
                    foreach ($end['shots'] as $shotIndex => $shot) {
                        if (is_array($shot) && isset($shot['playerId']) && (int) $shot['playerId'] === $dropPlayerId) {
                            $shot['playerId'] = $keepPlayerId;
                            $end['shots'][$shotIndex] = $shot;
                        }
                    }
                }
                $data['ends'][$index] = $end;
            }
        }

        return $data;
    }

    /**
     * @param array<int|string, mixed> $map
     * @return array<int|string, mixed>
     */
    private function rekeyPlayerMap(array $map, int $keepPlayerId, int $dropPlayerId): array
    {
        $next = [];
        foreach ($map as $key => $value) {
            $resolved = (int) $key === $dropPlayerId ? $keepPlayerId : $key;
            if (!array_key_exists($resolved, $next)) {
                $next[$resolved] = $value;
            }
        }

        return $next;
    }
}
