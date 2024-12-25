<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UserProvider implements UserProviderInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Load the user by identifier (username or email)
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Try finding by username first
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'username' => $identifier
        ]);

        // If not found by username, try email
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => $identifier
            ]);
        }

        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

        return $user;
    }

    /**
     * Refresh the user object (not necessary for this implementation)
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    /**
     * Check if the user class is supported
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
