<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\UpsertCompetitionRequest;
use App\Dto\Response\CompetitionItem;
use App\Entity\Competition;
use App\Repository\CompetitionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class CompetitionService
{
    public function __construct(
        private CompetitionRepository $competitions,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<CompetitionItem>
     */
    public function listAll(): array
    {
        $out = [];
        foreach ($this->competitions->findAllOrdered() as $competition) {
            $out[] = $this->toItem($competition);
        }

        return $out;
    }

    public function create(UpsertCompetitionRequest $req): CompetitionItem
    {
        $competition = new Competition(
            name: trim($req->name),
            eventDate: new DateTimeImmutable($req->eventDate),
            country: trim($req->country),
            context: $this->normalizeOptionalString($req->context),
        );

        $this->em->persist($competition);
        $this->em->flush();

        return $this->toItem($competition);
    }

    public function update(Competition $competition, UpsertCompetitionRequest $req): CompetitionItem
    {
        $competition->setName(trim($req->name));
        $competition->setEventDate(new DateTimeImmutable($req->eventDate));
        $competition->setCountry(trim($req->country));
        $competition->setContext($this->normalizeOptionalString($req->context));

        $this->em->flush();

        return $this->toItem($competition);
    }

    public function delete(Competition $competition): void
    {
        $this->em->remove($competition);
        $this->em->flush();
    }

    private function toItem(Competition $competition): CompetitionItem
    {
        return new CompetitionItem(
            id: (int) $competition->getId(),
            name: $competition->getName(),
            eventDate: $competition->getEventDate()->format('Y-m-d'),
            country: $competition->getCountry(),
            context: $competition->getContext(),
        );
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
