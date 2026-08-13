<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameParticipantRepository;
use App\Repository\PlayerRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access to a match when the authenticated user is either:
 * - the match creator (owner), or
 * - linked to a player who participates in the match.
 */
final class GameVoter extends Voter
{
    public const VIEW = 'GAME_VIEW';
    public const EDIT = 'GAME_EDIT';

    public function __construct(
        private PlayerRepository $players,
        private GameParticipantRepository $participants,
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

        return $this->isParticipant($game, $user);
    }

    private function isOwner(Game $game, User $user): bool
    {
        $createdBy = $game->getCreatedBy();
        if ($createdBy === null) {
            return false;
        }

        return (int) $createdBy->getId() === (int) $user->getId();
    }

    private function isParticipant(Game $game, User $user): bool
    {
        $player = $this->players->findOneByUserId((int) $user->getId());
        if ($player === null || $player->getId() === null) {
            return false;
        }

        $participantIds = $this->participants->findAllPlayerIdsByGame($game);

        return in_array((int) $player->getId(), $participantIds, true);
    }
}
