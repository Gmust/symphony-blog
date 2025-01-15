<?php

namespace App\Controller;

use App\Form\UserProfileType;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(Request $request, UserService $userService): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            try {
                $userService->updateUserProfile($user, $username, $currentPassword, $newPassword);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
