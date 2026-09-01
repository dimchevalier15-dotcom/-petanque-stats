<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'match_balls')]
class GameBall
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameEnd::class)]
    #[ORM\JoinColumn(name: 'end_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GameEnd $end;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Player $player;

    // 1-based chronological order of the shot within the end (global across all players)
    #[ORM\Column(name: 'sequence_order', type: 'smallint')]
    private int $sequenceOrder;

    // Note value: -2, -1, 0, 1, 2
    #[ORM\Column(type: 'smallint')]
    private int $note;

    // Shot type for this ball: 'point' | 'tir'
    #[ORM\Column(name: 'shot_type', type: 'string', length: 6)]
    private string $shotType;

    // Optional distance in meters, estimated at the time the ball was played. No statistical use yet.
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $distance = null;

    #[ORM\Column(name: 'is_cochonnet', type: 'boolean', options: ['default' => false])]
    private bool $isCochonnet = false;

    public function __construct(
        GameEnd $end,
        Player $player,
        int $sequenceOrder,
        int $note,
        string $shotType = 'point',
        ?float $distance = null,
        bool $isCochonnet = false,
    ) {
        $this->end = $end;
        $this->player = $player;
        $this->sequenceOrder = $sequenceOrder;
        $this->note = $note;
        $this->shotType = in_array($shotType, ['point','tir'], true) ? $shotType : 'point';
        $this->distance = $distance;
        $this->isCochonnet = $isCochonnet;
    }

    public function getId(): ?int { return $this->id; }
    public function getPlayer(): Player { return $this->player; }
    public function getSequenceOrder(): int { return $this->sequenceOrder; }
    public function getNote(): int { return $this->note; }
    public function getShotType(): string { return $this->shotType; }
    public function getDistance(): ?float { return $this->distance; }
    public function isCochonnet(): bool { return $this->isCochonnet; }
}
