<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthController extends AbstractController
{
    private $tokenStorage;
    private $entityManager;


    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface  $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, PasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;


        if (empty($username) || empty($password)) {
            return $this->json(['error' => 'Missing credentials'], 400);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user || !$passwordHasher->verify($user->getPassword(), $password)) {
            return $this->json(['error' => 'Invalid credntials'], 401);
        }

        return $this->json([
            'message' => 'Login successfull',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ]
        ], 200);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): JsonResponse
    {
        return $this->json(['message' => 'Logout successful'], 200);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $email = $data['email'] ?? null;

        if (empty($username) || empty($password) || empty($email)) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' =>  $username]);

        if ($existingUser) {
            return $this->json(['error' => 'Username already taaken'], 400);
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            return $this->json(['error' => 'Email already registered'], 400);
        }

        $user = new User();
        $user->setUsername($username)
            ->setEmail($email)
            ->setPassword($passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Registration Successful',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ]
        ], 201);
    }
}
