<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PostFixture extends Fixture implements FixtureGroupInterface
{
    private UserProviderInterface $userProvider;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserProviderInterface $userProvider, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userProvider = $userProvider;
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $adminEmail = 'user1@example.com';
        try {
            $user = $this->userProvider->loadUserByIdentifier($adminEmail);
        } catch (\Exception $e) {
            $user = new User();
            $user->setUsername('admin');
            $user->setEmail($adminEmail);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'admin_password'));
            $manager->persist($user);
            $manager->flush();
        }

        for ($i = 1; $i <= 10; $i++) {
            $post = new Post();
            $post->setTitle('Post Title ' . $i)
                ->setContent('Content of post ' . $i)
                ->setUser($user)
                ->setCreatedAt(new \DateTime());
            $manager->persist($post);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['posts'];
    }
}
