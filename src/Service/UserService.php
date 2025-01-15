<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class UserService
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function updateUserProfile(User $user, ?string $username, ?string $currentPassword, ?string $newPassword): void
    {
        if ($username) {
            $user->setUsername($username);
        }

        if ($currentPassword && $newPassword) {
            if ($this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $encodedPassword = $this->passwordHasher->encodePassword($user, $newPassword);
                $user->setPassword($encodedPassword);
            } else {
                throw new \Exception('Current password is incorrect.');
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
