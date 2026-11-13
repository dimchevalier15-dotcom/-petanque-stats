<?php

declare(strict_types=1);

namespace App\Service\Tournament;

use App\Dto\Request\CreateTournamentRequest;
use App\Dto\Request\UpdateTournamentRequest;
use App\Dto\Response\TournamentDetailResponse;
use App\Dto\Response\TournamentSummaryResponse;
use App\Dto\Response\TournamentUserSearchResponse;
use App\Entity\Tournament;
use App\Entity\TournamentMember;
use App\Entity\User;
use App\Repository\TournamentMemberRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class TournamentCatalogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TournamentRepository $tournaments,
        private TournamentMemberRepository $members,
        private UserRepository $users,
        private TournamentContext $context,
        private TournamentViewFactory $views,
        private TournamentLaneCatalog $lanes,
    ) {
    }

    /**
     * @return list<TournamentSummaryResponse>
     */
    public function listFor(User $user): array
    {
        $summaries = [];
        foreach ($this->tournaments->findForUser($user) as $tournament) {
            $summaries[] = $this->views->summary($tournament);
        }

        return $summaries;
    }

    public function detail(User $user, int $tournamentId): TournamentDetailResponse
    {
        return $this->views->detail($user, $this->context->requireMember($user, $tournamentId));
    }

    public function publicDetail(string $publicUuid): TournamentDetailResponse
    {
        $tournament = $this->tournaments->findOneByPublicUuid($publicUuid);
        if (!$tournament instanceof Tournament) {
            throw new TournamentNotFoundException();
        }

        return $this->views->publicDetail($tournament);
    }

    public function create(User $user, CreateTournamentRequest $input): TournamentDetailResponse
    {
        if (!$user->isMaster()) {
            throw new TournamentAccessException();
        }
        $this->assertSettings($input->minPlayersPerTeam, $input->maxPlayersPerTeam, $input->startsAt, $input->endsAt);
        $excluded = $this->lanes->normalizeExcluded($input->excludedLaneNumbers);
        $tournament = new Tournament(
            trim($input->name),
            $this->nullableText($input->description),
            $input->minPlayersPerTeam,
            $input->maxPlayersPerTeam,
            $input->pointsForWin,
            $input->pointsForDraw,
            $input->drawsAllowed,
            $input->numberOfLanes,
            $excluded,
            $this->date($input->startsAt),
            $this->date($input->endsAt),
            $user,
        );
        $this->entityManager->persist($tournament);
        foreach ($this->uniqueIds($input->administratorUserIds) as $userId) {
            $administrator = $this->requireUser($userId);
            $this->entityManager->persist(new TournamentMember($tournament, $administrator, true));
        }
        $this->entityManager->flush();

        return $this->views->detail($user, $tournament);
    }

    public function update(User $user, int $tournamentId, UpdateTournamentRequest $input): TournamentDetailResponse
    {
        $tournament = $this->context->requireAdmin($user, $tournamentId);
        $this->assertSettings($input->minPlayersPerTeam, $input->maxPlayersPerTeam, $input->startsAt, $input->endsAt);
        $tournament->updateSettings(
            trim($input->name),
            $this->nullableText($input->description),
            $input->minPlayersPerTeam,
            $input->maxPlayersPerTeam,
            $input->pointsForWin,
            $input->pointsForDraw,
            $input->drawsAllowed,
            $input->numberOfLanes,
            $this->lanes->normalizeExcluded($input->excludedLaneNumbers),
            $this->date($input->startsAt),
            $this->date($input->endsAt),
        );
        $this->entityManager->flush();

        return $this->views->detail($user, $tournament);
    }

    /**
     * @return list<TournamentUserSearchResponse>
     */
    public function searchUsers(User $user, string $query): array
    {
        if (!$user->isMaster() && !$this->members->userIsAdminSomewhere($user)) {
            throw new TournamentAccessException();
        }
        $prefix = trim($query);
        if (mb_strlen($prefix) < 2) {
            return [];
        }
        $results = [];
        foreach ($this->users->searchByEmailPrefix($prefix) as $found) {
            $results[] = new TournamentUserSearchResponse((int) $found->getId(), $found->getEmail());
        }

        return $results;
    }

    private function assertSettings(int $minPlayers, int $maxPlayers, ?string $startsAt, ?string $endsAt): void
    {
        if ($maxPlayers < $minPlayers) {
            throw new TournamentRuleException('invalid_settings', 'The maximum number of players must be at least the minimum.', 422);
        }
        $start = $this->date($startsAt);
        $end = $this->date($endsAt);
        if ($start !== null && $end !== null && $end < $start) {
            throw new TournamentRuleException('invalid_settings', 'The end date must be on or after the start date.', 422);
        }
    }

    private function date(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false) {
            throw new TournamentRuleException('invalid_settings', 'A date is invalid.', 422);
        }

        return $date;
    }

    private function nullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function uniqueIds(array $ids): array
    {
        $unique = [];
        foreach ($ids as $id) {
            $unique[(int) $id] = (int) $id;
        }

        return array_values($unique);
    }

    private function requireUser(int $userId): User
    {
        $user = $this->users->find($userId);
        if (!$user instanceof User) {
            throw new TournamentRuleException('user_not_found', 'A selected user does not exist.', 422);
        }

        return $user;
    }
}
