<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Form\KeyValueStoreType;
use App\Service\UserService;
use App\Service\KeyValueStoreService;
use App\Repository\KeyValueStoreRepository;
use App\Entity\KeyValueStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private $keyValueStoreRepository;
    private $keyValueStoreService;

    public function __construct(KeyValueStoreRepository $keyValueStoreRepository, KeyValueStoreService $keyValueStoreService)
    {
        $this->keyValueStoreRepository = $keyValueStoreRepository;
        $this->keyValueStoreService = $keyValueStoreService;
    }

    #[Route('/home', name: 'app_home')]
    public function index(Request $request, UserService $userService): Response
    {
        $user = $this->getUser();
        $aboutMeData = $this->keyValueStoreRepository->findBy(['user' => $user]);

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
        $keyValueStore = $this->keyValueStoreRepository->findById($id);

        if ($keyValueStore) {
            $this->keyValueStoreService->delete($keyValueStore);
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/api/home', name: 'api_get_home_data', methods: ['GET'])]
    public function apiGetHomeData(SessionInterface $session): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
        $aboutMeData = $this->keyValueStoreRepository->findBy(['user' => $user]);
        return $this->json($aboutMeData);
    }

    #[Route('/api/home', name: 'api_add_home_data', methods: ['POST'])]
    public function apiAddHomeData(Request $request, SessionInterface $session): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);

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

        return $this->json($keyValueStore, Response::HTTP_CREATED);
    }

    #[Route('/api/home/{id}', name: 'api_delete_home_data', methods: ['DELETE'])]
    public function apiDeleteHomeData(int $id, SessionInterface $session): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $keyValueStore = $this->keyValueStoreRepository->findById($id);

        if ($keyValueStore && $keyValueStore->getUser()->getId() === $userId) {
            $this->keyValueStoreService->delete($keyValueStore);
            return $this->json(['message' => 'Key-value pair deleted successfully'], Response::HTTP_NO_CONTENT);
        }

        return $this->json(['message' => 'Key-value pair not found'], Response::HTTP_NOT_FOUND);
    }
}
