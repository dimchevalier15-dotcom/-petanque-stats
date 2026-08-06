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

    // 0-based order of the ball within player's notes for that end
    #[ORM\Column(name: 'ball_index', type: 'smallint')]
    private int $index;

    // Note value: -2, -1, 0, 1, 2
    #[ORM\Column(type: 'smallint')]
    private int $note;

    // Shot type for this ball: 'point' | 'tir'
    #[ORM\Column(name: 'shot_type', type: 'string', length: 6)]
    private string $shotType;

    public function __construct(GameEnd $end, Player $player, int $index, int $note, string $shotType = 'point')
    {
        $this->end = $end;
        $this->player = $player;
        $this->index = $index;
        $this->note = $note;
        $this->shotType = in_array($shotType, ['point','tir'], true) ? $shotType : 'point';
    }

    public function getId(): ?int { return $this->id; }
}
