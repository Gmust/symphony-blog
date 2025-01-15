<?php

namespace App\Controller;

use App\Form\UserProfileType;
use App\Form\KeyValueStoreType;
use App\Service\UserService;
use App\Service\KeyValueStoreService;
use App\Repository\KeyValueStoreRepository;
use App\Entity\KeyValueStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
}
