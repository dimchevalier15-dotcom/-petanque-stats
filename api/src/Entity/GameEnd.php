<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'match_ends')]
class GameEnd
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'match_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    // 1-based index of the end
    #[ORM\Column(name: 'end_index', type: 'integer')]
    private int $index;

    // 'A' or 'B'
    #[ORM\Column(type: 'string', length: 1)]
    private string $winner;

    #[ORM\Column(type: 'smallint')]
    private int $points;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private bool $canceled = false;

    public function __construct(Game $game, int $index, string $winner, int $points, bool $canceled = false)
    {
        $this->game = $game;
        $this->index = $index;
        $this->winner = $winner;
        $this->points = $points;
        $this->canceled = $canceled;
    }

    public function getId(): ?int { return $this->id; }
    public function getGame(): Game { return $this->game; }
    public function getIndex(): int { return $this->index; }
    public function getWinner(): string { return $this->winner; }
    public function getPoints(): int { return $this->points; }
    public function isCanceled(): bool { return $this->canceled; }
}
