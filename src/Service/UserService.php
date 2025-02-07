<?php

namespace App\Service;

use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Transformer\UserTransformer;

class UserService
{
    private $userRepository;
    private $passwordHasher;
    private $userTransformer;

    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, UserTransformer $userTransformer)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->userTransformer = $userTransformer;
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

        $this->userRepository->save($user);
    }

    public function transformUser(User $user): array
    {
        return $this->userTransformer->transform($user);
    }

    public function reverseTransformUser(array $data, User $user): User
    {
        return $this->userTransformer->reverseTransform($data, $user);
    }
}
