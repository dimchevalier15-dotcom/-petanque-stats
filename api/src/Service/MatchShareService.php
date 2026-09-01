<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\SharedMatchRecapResponse;
use App\Entity\Game;
use App\Repository\GameEndRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MatchShareNotFoundException extends \RuntimeException
{
}

final class MatchShareService
{
    public function __construct(
        private GameRepository $games,
        private GameEndRepository $ends,
        private MatchSummaryService $summary,
        private MatchContextService $context,
        private EntityManagerInterface $em,
        private string $frontendBaseUrl,
    ) {
    }

    public function ensureShareUuid(Game $game): ?string
    {
        $existing = $game->getShareUuid();
        if ($existing !== null && $existing !== '') {
            return $existing;
        }

        if ($this->ends->countByGame($game) === 0) {
            return null;
        }

        $uuid = $this->generateUuid();
        $game->setShareUuid($uuid);
        $this->em->flush();

        return $uuid;
    }

    public function getPublicRecap(string $uuid): SharedMatchRecapResponse
    {
        $game = $this->games->findOneByShareUuid($uuid);
        if ($game === null) {
            throw new MatchShareNotFoundException();
        }

        $summary = $this->summary->getSummary((int) $game->getId());
        if ($summary === null) {
            throw new MatchShareNotFoundException();
        }

        $context = $this->context->getContext((int) $game->getId());
        if ($context === null) {
            throw new MatchShareNotFoundException();
        }

        return new SharedMatchRecapResponse(
            summary: $summary,
            context: $context,
            competitionLabel: $this->resolveCompetitionLabel($game),
        );
    }

    public function buildPublicUrl(string $uuid): string
    {
        return rtrim($this->frontendBaseUrl, '/').'/recap/'.$uuid;
    }

    private function resolveCompetitionLabel(Game $game): ?string
    {
        $competition = $game->getCompetition();
        if ($competition !== null) {
            return $competition->getName().' - '.$competition->getEventDate()->format('Y');
        }

        return $game->getCompetitionName();
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
