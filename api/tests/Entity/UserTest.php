<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testNewUserHasSimplePlayerRole(): void
    {
        $user = new User('player@test.local');

        self::assertSame(UserRole::SIMPLE_PLAYER, $user->getRole());
        self::assertFalse($user->isMaster());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testMasterRoleGrantsAdminAccess(): void
    {
        $user = new User('master@test.local');
        $user->setRole(UserRole::MASTER);

        self::assertTrue($user->isMaster());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }
}
