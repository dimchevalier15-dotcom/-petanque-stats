<?php

declare(strict_types=1);

namespace App\Service\Tournament;

final class TournamentRuleException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode = 409,
    ) {
        parent::__construct($message);
    }
}
