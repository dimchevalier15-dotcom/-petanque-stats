<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\UpsertLiveMatchRequest;
use App\Dto\Response\CreateLiveMatchResponse;
use App\Dto\Response\LiveMatchResponse;
use App\Entity\LiveMatch;
use App\Repository\LiveMatchRepository;
use Doctrine\ORM\EntityManagerInterface;

final class LiveMatchNotFoundException extends \RuntimeException
{
}

final class LiveMatchFinishedException extends \RuntimeException
{
}

final class LiveMatchNotActiveException extends \RuntimeException
{
}

final class LiveMatchService
{
    public function __construct(
        private LiveMatchRepository $liveMatches,
        private EntityManagerInterface $em,
        private string $frontendBaseUrl,
    ) {
    }

    public function create(UpsertLiveMatchRequest $req): CreateLiveMatchResponse
    {
        $uuid = $this->generateUuid();
        $liveMatch = new LiveMatch($uuid, $req->data);
        $this->em->persist($liveMatch);
        $this->em->flush();

        return new CreateLiveMatchResponse(
            uuid: $uuid,
            url: $this->buildPublicUrl($uuid),
        );
    }

    public function getByUuid(string $uuid): LiveMatchResponse
    {
        $liveMatch = $this->findOrFail($uuid);

        return $this->toResponse($liveMatch);
    }

    public function update(string $uuid, UpsertLiveMatchRequest $req): LiveMatchResponse
    {
        $liveMatch = $this->findOrFail($uuid);

        try {
            $liveMatch->replaceData($req->data);
        } catch (\DomainException) {
            throw new LiveMatchFinishedException();
        }

        $this->em->flush();

        return $this->toResponse($liveMatch);
    }

    public function finish(string $uuid): LiveMatchResponse
    {
        $liveMatch = $this->findOrFail($uuid);

        try {
            $liveMatch->finish();
        } catch (\DomainException) {
            throw new LiveMatchNotActiveException();
        }

        $this->em->flush();

        return $this->toResponse($liveMatch);
    }

    public function delete(string $uuid): void
    {
        $liveMatch = $this->findOrFail($uuid);
        $this->em->remove($liveMatch);
        $this->em->flush();
    }

    private function findOrFail(string $uuid): LiveMatch
    {
        $liveMatch = $this->liveMatches->findOneByUuid($uuid);
        if ($liveMatch === null) {
            throw new LiveMatchNotFoundException();
        }

        return $liveMatch;
    }

    private function toResponse(LiveMatch $liveMatch): LiveMatchResponse
    {
        return new LiveMatchResponse(
            uuid: $liveMatch->getUuid(),
            status: $liveMatch->getStatus(),
            data: $liveMatch->getData(),
            createdAt: $liveMatch->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $liveMatch->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            finishedAt: $liveMatch->getFinishedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    private function buildPublicUrl(string $uuid): string
    {
        return rtrim($this->frontendBaseUrl, '/').'/live/'.$uuid;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
