<?php

namespace App\Controller;

use App\Repository\KeyValueStoreRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ThemeController extends AbstractController
{
    private KeyValueStoreRepository $keyValueStoreRepository;

    public function __construct(KeyValueStoreRepository $keyValueStoreRepository)
    {
        $this->keyValueStoreRepository = $keyValueStoreRepository;
    }

    #[Route('/toggle-theme', name: 'toggle_theme', methods: ['POST'])]
    public function toggleTheme(Request $request): Response
    {
        $session = $request->getSession();
        $currentTheme = $session->get('theme', 'light');
        $newTheme = $currentTheme === 'light' ? 'dark' : 'light';
        $session->set('theme', $newTheme);

        $user = $this->getUser();

        if ($user) {
            $this->keyValueStoreRepository->updateKeyValue($user->getUserIdentifier(), 'theme', $newTheme);
        }

        return new Response('Theme toggled to' . $newTheme);
    }
}
