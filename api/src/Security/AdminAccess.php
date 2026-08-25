<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;

final class AdminAccess
{
    private const ADMIN_EMAIL = 'dimchevalier15@gmail.com';

    public static function isAdmin(User $user): bool
    {
        return mb_strtolower($user->getEmail()) === self::ADMIN_EMAIL;
    }
}
