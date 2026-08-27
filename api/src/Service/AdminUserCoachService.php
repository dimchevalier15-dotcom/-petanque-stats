<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Club;
use App\Entity\User;
use App\Repository\ClubRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AdminUserCoachService
{
    public function __construct(
        private UserRepository $users,
        private ClubRepository $clubs,
        private MeResponseFactory $meResponseFactory,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws UserNotFoundException
     * @throws ClubNotFoundException
     */
    public function updateCoachClub(string $email, ?int $clubId): \App\Dto\Response\MeResponse
    {
        $user = $this->users->findOneByEmail($email);
        if ($user === null) {
            throw new UserNotFoundException();
        }

        if ($clubId === null) {
            $user->setCoachForClub(null);
        } else {
            $club = $this->clubs->find($clubId);
            if (!$club instanceof Club) {
                throw new ClubNotFoundException();
            }
            $user->setCoachForClub($club);
        }

        $this->em->flush();

        return $this->meResponseFactory->fromUser($user);
    }
}

final class UserNotFoundException extends \RuntimeException {}
