<?php

declare(strict_types=1);

namespace App\Enum;

enum TournamentMatchStatus: string
{
    case SCHEDULED = 'scheduled';
    case FINISHED = 'finished';
}
