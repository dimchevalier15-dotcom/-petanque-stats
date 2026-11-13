<?php

declare(strict_types=1);

namespace App\Enum;

enum TournamentPlayoffRound: string
{
    case QUARTER = 'quarter';
    case SEMI = 'semi';
    case FINAL = 'final';
}
