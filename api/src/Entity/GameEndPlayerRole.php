<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PlayerRole;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'match_end_player_roles')]
#[ORM\UniqueConstraint(name: 'uniq_end_player_role', columns: ['end_id', 'player_id'])]
class GameEndPlayerRole
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

    #[ORM\Column(type: 'string', length: 10, enumType: PlayerRole::class)]
    private PlayerRole $role;

    public function __construct(GameEnd $end, Player $player, PlayerRole $role)
    {
        $this->end = $end;
        $this->player = $player;
        $this->role = $role;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): PlayerRole
    {
        return $this->role;
    }
}
