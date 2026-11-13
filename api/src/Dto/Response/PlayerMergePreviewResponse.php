<?php

declare(strict_types=1);

namespace App\Dto\Response;

final class PlayerMergePreviewResponse
{
    public function __construct(
        public PlayerMergeSideResponse $keep,
        public PlayerMergeSideResponse $drop,
        public bool $requiresUserChoice,
    ) {
    }
}
