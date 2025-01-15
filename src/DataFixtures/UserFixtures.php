<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class UserFixtures extends Fixture implements FixtureGroupInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $firstUser = new User();
        $firstUser->setUsername("User1");
        $firstUser->setEmail("user1@example.com");
        $firstUser->setPassword($this->passwordHasher->hashPassword($firstUser, "Password1"));
        $manager->persist($firstUser);

        $secondUser = new User();
        $secondUser->setUsername("User2");
        $secondUser->setEmail("user2@example.com");
        $secondUser->setPassword($this->passwordHasher->hashPassword($secondUser, "Password1"));
        $manager->persist($secondUser);

        $firstPost = new Post();
        $firstPost->setTitle("First title post");
        $firstPost->setContent("Content for first post");
        $firstPost->setUser($firstUser);
        $manager->persist($firstPost);

        $secondPost = new Post();
        $secondPost->setTitle("Second title post");
        $secondPost->setContent("Content for second post");
        $secondPost->setUser($secondUser);
        $manager->persist($secondPost);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['users'];
    }
}
