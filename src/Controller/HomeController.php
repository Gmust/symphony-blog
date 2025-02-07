<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\KeyValueStore;
use App\Form\UserProfileType;
use App\Form\KeyValueStoreType;
use App\Service\UserService;
use App\Service\KeyValueStoreService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class HomeController extends AbstractController
{
    private KeyValueStoreService $keyValueStoreService;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(
        KeyValueStoreService    $keyValueStoreService,
        EntityManagerInterface  $entityManager,
        SerializerInterface     $serializer
    )
    {
        $this->keyValueStoreService = $keyValueStoreService;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('/home', name: 'app_home')]
    public function index(Request $request, UserService $userService): Response
    {
        $user = $this->getUser();
        $aboutMeData = $this->keyValueStoreService->getAllByUser($user);

        $userProfileForm = $this->createForm(UserProfileType::class, $user);
        $userProfileForm->handleRequest($request);

        $keyValueStore = new KeyValueStore();
        $keyValueStoreForm = $this->createForm(KeyValueStoreType::class, $keyValueStore);
        $keyValueStoreForm->handleRequest($request);

        if ($userProfileForm->isSubmitted() && $userProfileForm->isValid()) {
            $username = $userProfileForm->get('username')->getData();
            $currentPassword = $userProfileForm->get('currentPassword')->getData();
            $newPassword = $userProfileForm->get('newPassword')->getData();

            try {
                $userService->updateUserProfile($user, $username, $currentPassword, $newPassword);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_home');
        }

        if ($keyValueStoreForm->isSubmitted() && $keyValueStoreForm->isValid()) {
            $key = $keyValueStoreForm->get('key')->getData();
            $value = $keyValueStoreForm->get('value')->getData();  // This is already an array

            $keyValueStore->setUser($user);
            $keyValueStore->setKey($key);
            $keyValueStore->setValue($value);

            $this->keyValueStoreService->save($keyValueStore);

            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'form' => $userProfileForm->createView(),
            'keyValueStoreForm' => $keyValueStoreForm->createView(),
            'aboutMeData' => $aboutMeData
        ]);
    }

    #[Route('/home/delete/{id}', name: 'app_home_delete')]
    public function delete(int $id): Response
    {
        $keyValueStore = $this->keyValueStoreService->findById($id);

        if ($keyValueStore) {
            $this->keyValueStoreService->delete($keyValueStore);
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/api/home/about-me', name: 'api_get_about_me', methods: ['GET'])]
    public function apiGetAboutMe(SessionInterface $session): JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $aboutMeData = $this->keyValueStoreService->findByUser($user);

        $jsonContent = $this->serializer->serialize($aboutMeData, 'json', ['groups' => 'key_value:read']);
        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/api/home/about-me', name: 'api_add_about_me', methods: ['POST'])]
    public function apiAddAboutMe(Request $request, SessionInterface $session): JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        $data = json_decode($request->getContent(), true);
        $key = $data['key'] ?? null;
        $value = $data['value'] ?? null;

        if (!$key || !$value) {
            return $this->json(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $keyValueStore = new KeyValueStore();
        $keyValueStore->setUser($user);
        $keyValueStore->setKey($key);
        $keyValueStore->setValue((array)$value);

        $this->keyValueStoreService->save($keyValueStore);

        return $this->json($keyValueStore, Response::HTTP_CREATED, [], ['groups' => 'key_value:read']);
    }

    #[Route('/api/home/about-me/{id}', name: 'api_delete_about_me', methods: ['DELETE'])]
    public function apiDeleteAboutMe(int $id, SessionInterface $session): JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $keyValueStore = $this->keyValueStoreService->findById($id);

        if ($keyValueStore && $keyValueStore->getUser()->getId() === $userId) {
            $this->keyValueStoreService->delete($keyValueStore);
            return $this->json(['message' => 'Key-value pair deleted successfully'], Response::HTTP_NO_CONTENT);
        }

        return $this->json(['message' => 'Key-value pair not found'], Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/user/update', name: 'api_update_user', methods: ['PUT', 'PATCH'])]
    public function apiUpdateUser(
        Request                     $request,
        SessionInterface            $session,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface          $validator
    ): JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $currentPassword = $data['currentPassword'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if ($username) {
            $user->setUsername($username);
        }

        if ($email) {
            $existingEmail = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingEmail && $existingEmail !== $user) {
                return $this->json(['message' => 'Email already exists'], Response::HTTP_CONFLICT);
            }
            $user->setEmail($email);
        }

        if ($currentPassword && $newPassword) {
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return $this->json(['message' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
            }
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsString = (string)$errors;
            return $this->json(['message' => 'Validation error', 'errors' => $errorsString], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $jsonContent = $this->serializer->serialize($user, 'json', ['groups' => 'user:read']);
        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }
}
