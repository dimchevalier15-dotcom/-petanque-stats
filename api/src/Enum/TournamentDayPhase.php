<?php

declare(strict_types=1);

namespace App\Enum;

enum TournamentDayPhase: string
{
    case CHAMPIONSHIP = 'championship';
    case PLAYOFF = 'playoff';
}
