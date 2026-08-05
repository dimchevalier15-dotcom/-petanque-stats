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

    public function __construct(Game $game, Player $player, string $team, int $position)
    {
        $this->game = $game;
        $this->player = $player;
        $this->team = $team;
        $this->position = $position;
    }

    public function getId(): ?int { return $this->id; }
}
