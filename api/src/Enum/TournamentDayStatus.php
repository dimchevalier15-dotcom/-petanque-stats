<?php

declare(strict_types=1);

namespace App\Enum;

enum TournamentDayStatus: string
{
    case GENERATED = 'generated';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';
}
