<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TournamentFormat;
use App\Enum\TournamentStatus;
use App\Repository\TournamentRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Table(name: 'tournaments')]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'string', length: 64, enumType: TournamentFormat::class)]
    private TournamentFormat $format;

    #[ORM\Column(type: 'string', length: 20, enumType: TournamentStatus::class)]
    private TournamentStatus $status;

    #[ORM\Column(name: 'min_players_per_team', type: 'integer')]
    private int $minPlayersPerTeam;

    #[ORM\Column(name: 'max_players_per_team', type: 'integer')]
    private int $maxPlayersPerTeam;

    #[ORM\Column(name: 'points_for_win', type: 'integer')]
    private int $pointsForWin;

    #[ORM\Column(name: 'points_for_draw', type: 'integer')]
    private int $pointsForDraw;

    #[ORM\Column(name: 'draws_allowed', type: 'boolean')]
    private bool $drawsAllowed;

    #[ORM\Column(name: 'number_of_lanes', type: 'integer')]
    private int $numberOfLanes;

    /**
     * @var list<int>
     */
    #[ORM\Column(name: 'excluded_lane_numbers', type: 'json')]
    private array $excludedLaneNumbers;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'starts_at', type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $startsAt;

    #[ORM\Column(name: 'ends_at', type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $endsAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_user_id', nullable: false, onDelete: 'RESTRICT')]
    private User $createdBy;

    /**
     * @param list<int> $excludedLaneNumbers
     */
    public function __construct(
        string $name,
        ?string $description,
        int $minPlayersPerTeam,
        int $maxPlayersPerTeam,
        int $pointsForWin,
        int $pointsForDraw,
        bool $drawsAllowed,
        int $numberOfLanes,
        array $excludedLaneNumbers,
        ?DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
        User $createdBy,
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->format = TournamentFormat::CHAMPIONSHIP_SINGLE_PLAYOFFS_8;
        $this->status = TournamentStatus::UPCOMING;
        $this->minPlayersPerTeam = $minPlayersPerTeam;
        $this->maxPlayersPerTeam = $maxPlayersPerTeam;
        $this->pointsForWin = $pointsForWin;
        $this->pointsForDraw = $pointsForDraw;
        $this->drawsAllowed = $drawsAllowed;
        $this->numberOfLanes = $numberOfLanes;
        $this->excludedLaneNumbers = $excludedLaneNumbers;
        $this->createdAt = new DateTimeImmutable();
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->createdBy = $createdBy;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getFormat(): TournamentFormat
    {
        return $this->format;
    }

    public function getStatus(): TournamentStatus
    {
        return $this->status;
    }

    public function setStatus(TournamentStatus $status): void
    {
        $this->status = $status;
    }

    public function getMinPlayersPerTeam(): int
    {
        return $this->minPlayersPerTeam;
    }

    public function getMaxPlayersPerTeam(): int
    {
        return $this->maxPlayersPerTeam;
    }

    public function getPointsForWin(): int
    {
        return $this->pointsForWin;
    }

    public function getPointsForDraw(): int
    {
        return $this->pointsForDraw;
    }

    public function drawsAllowed(): bool
    {
        return $this->drawsAllowed;
    }

    public function getNumberOfLanes(): int
    {
        return $this->numberOfLanes;
    }

    /**
     * @return list<int>
     */
    public function getExcludedLaneNumbers(): array
    {
        return self::integerList($this->excludedLaneNumbers);
    }

    /**
     * @param list<int|string> $values
     * @return list<int>
     */
    private static function integerList(array $values): array
    {
        $lanes = [];
        foreach ($values as $lane) {
            $lanes[] = (int) $lane;
        }

        return $lanes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    /**
     * @param list<int> $excludedLaneNumbers
     */
    public function updateSettings(
        string $name,
        ?string $description,
        int $minPlayersPerTeam,
        int $maxPlayersPerTeam,
        int $pointsForWin,
        int $pointsForDraw,
        bool $drawsAllowed,
        int $numberOfLanes,
        array $excludedLaneNumbers,
        ?DateTimeImmutable $startsAt,
        ?DateTimeImmutable $endsAt,
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->minPlayersPerTeam = $minPlayersPerTeam;
        $this->maxPlayersPerTeam = $maxPlayersPerTeam;
        $this->pointsForWin = $pointsForWin;
        $this->pointsForDraw = $pointsForDraw;
        $this->drawsAllowed = $drawsAllowed;
        $this->numberOfLanes = $numberOfLanes;
        $this->excludedLaneNumbers = $excludedLaneNumbers;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
    }
}
