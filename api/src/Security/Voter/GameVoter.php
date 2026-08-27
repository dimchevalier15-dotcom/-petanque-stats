<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameParticipantRepository;
use App\Repository\PlayerRepository;
use App\Security\ImpersonationResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access to a match when the authenticated user is either:
 * - the match creator (owner), or
 * - linked to a player who participates in the match.
 *
 * VIEW also allows admin impersonation of a participating player.
 */
final class GameVoter extends Voter
{
    public const VIEW = 'GAME_VIEW';
    public const EDIT = 'GAME_EDIT';

    public function __construct(
        private PlayerRepository $players,
        private GameParticipantRepository $participants,
        private RequestStack $requestStack,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Game;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Game $game */
        $game = $subject;

        if ($this->isOwner($game, $user)) {
            return true;
        }

        if ($attribute === self::VIEW) {
            if ($this->canCoachViewGame($game, $user)) {
                return true;
            }

            $impersonatePlayerId = $this->resolveImpersonatePlayerId($user);
            if ($impersonatePlayerId !== null) {
                $player = $this->players->find($impersonatePlayerId);
                $linkedUser = $player?->getUser();
                if ($linkedUser !== null && $this->isOwner($game, $linkedUser)) {
                    return true;
                }

                return $this->isGameParticipant($game, $impersonatePlayerId);
            }
        }

        return $this->isLinkedParticipant($game, $user);
    }

    private function isOwner(Game $game, User $user): bool
    {
        $createdBy = $game->getCreatedBy();
        if ($createdBy === null) {
            return false;
        }

        return (int) $createdBy->getId() === (int) $user->getId();
    }

    private function isLinkedParticipant(Game $game, User $user): bool
    {
        $player = $this->players->findOneByUserId((int) $user->getId());
        if ($player === null || $player->getId() === null) {
            return false;
        }

        return $this->isGameParticipant($game, (int) $player->getId());
    }

    private function isGameParticipant(Game $game, int $playerId): bool
    {
        $participantIds = $this->participants->findAllPlayerIdsByGame($game);

        return in_array($playerId, $participantIds, true);
    }

    private function canCoachViewGame(Game $game, User $user): bool
    {
        $coachClub = $user->getCoachForClub();
        if ($coachClub === null) {
            return false;
        }

        $coachClubId = (int) $coachClub->getId();
        foreach ($this->participants->findAllPlayerIdsByGame($game) as $playerId) {
            $player = $this->players->find($playerId);
            $playerClub = $player?->getClub();
            if ($playerClub !== null && (int) $playerClub->getId() === $coachClubId) {
                return true;
            }
        }

        return false;
    }

    private function resolveImpersonatePlayerId(User $user): ?int
    {
        if (!$user->isMaster()) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $header = $request->headers->get(ImpersonationResolver::HEADER);
        if ($header === null || $header === '' || !is_numeric($header) || (int) $header <= 0) {
            return null;
        }

        return (int) $header;
    }
}
