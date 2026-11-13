<?php

declare(strict_types=1);

namespace App\Enum;

enum TournamentStatus: string
{
    case UPCOMING = 'upcoming';
    case IN_PROGRESS = 'in_progress';
    case FINISHED = 'finished';
}
