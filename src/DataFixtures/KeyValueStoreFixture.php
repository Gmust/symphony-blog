<?php

namespace App\DataFixtures;

use App\Entity\KeyValueStore;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class KeyValueStoreFixture extends Fixture implements FixtureGroupInterface
{
    private $userRepository;

    private UserPasswordHasherInterface $passwordHasher;


    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Fetch an existing user by username or email
        $user = $this->userRepository->findOneByUsername('Reflexive');
        if (!$user) {
            // Create a new user if not found
            $user = new User();
            $user->setUsername('test');
            $user->setEmail('test@example.com');
            $user->setPassword($this->passwordHasher->hashPassword($user, 'admin_password'));
            $manager->persist($user);
        }

        // Key-value pairs for the "About Me" section
        $data = [
            ['key' => 'name', 'value' => ['John Doeclear']],
            ['key' => 'birthdate', 'value' => ['1990-01-01']],
            ['key' => 'location', 'value' => ['New York']],
            ['key' => 'hobbies', 'value' => ['reading', 'hiking', 'coding']],
            ['key' => 'favorite_quote', 'value' => ['To be, or not to be, that is the question.']],
            ['key' => 'fun_fact', 'value' => ['I once climbed Mount Everest']],
            ['key' => 'skills', 'value' => ['PHP', 'Symfony', 'JavaScript']],
            ['key' => 'favorite_books', 'value' => ['1984', 'Brave New World', 'Fahrenheit 451']],
            ['key' => 'languages_spoken', 'value' => ['English', 'Spanish', 'French']],
        ];

        foreach ($data as $item) {
            $keyValueStore = new KeyValueStore();
            $keyValueStore->setUser($user);
            $keyValueStore->setKey($item['key']);
            $keyValueStore->setValue($item['value']);
            $manager->persist($keyValueStore);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['about_me'];
    }
}
