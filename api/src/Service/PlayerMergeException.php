<?php

declare(strict_types=1);

namespace App\Service;

final class PlayerMergeException extends \RuntimeException
{
    public const NOT_FOUND = 'not_found';
    public const SAME_PLAYER = 'same_player';
    public const PLACEHOLDER = 'placeholder';
    public const USER_CHOICE_REQUIRED = 'user_choice_required';
    public const INVALID_USER = 'invalid_user';
    public const SAME_MATCH = 'same_match';
    public const SAME_TOURNAMENT = 'same_tournament';
    public const UNMOVED_REFERENCE = 'unmoved_reference';

    public function __construct(
        public readonly string $errorCode,
        public readonly ?string $detail = null,
    ) {
        parent::__construct($errorCode);
    }
}
