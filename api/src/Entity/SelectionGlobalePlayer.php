<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SelectionGlobalePlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SelectionGlobalePlayerRepository::class)]
#[ORM\Table(name: 'selection_globale_players')]
#[ORM\UniqueConstraint(name: 'UNIQ_SELECTION_PLAYER', columns: ['selection_globale_id', 'player_id'])]
class SelectionGlobalePlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SelectionGlobale::class)]
    #[ORM\JoinColumn(name: 'selection_globale_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private SelectionGlobale $selectionGlobale;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    public function __construct(SelectionGlobale $selectionGlobale, Player $player)
    {
        $this->selectionGlobale = $selectionGlobale;
        $this->player = $player;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSelectionGlobale(): SelectionGlobale
    {
        return $this->selectionGlobale;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
