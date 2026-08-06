<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'match_players')]
class GameParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'match_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Player $player;

    // 'A' or 'B'
    #[ORM\Column(type: 'string', length: 1)]
    private string $team;

    // 1..3
    #[ORM\Column(type: 'smallint')]
    private int $position;

    // default shot type for this player in this match: 'point' | 'tir'
    #[ORM\Column(name: 'default_shot_type', type: 'string', length: 6)]
    private string $defaultShotType = 'point';

    public function __construct(Game $game, Player $player, string $team, int $position, string $defaultShotType = 'point')
    {
        $this->game = $game;
        $this->player = $player;
        $this->team = $team;
        $this->position = $position;
        $this->defaultShotType = in_array($defaultShotType, ['point','tir'], true) ? $defaultShotType : 'point';
    }

    public function getId(): ?int { return $this->id; }
    public function getDefaultShotType(): string { return $this->defaultShotType; }
}
