<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    private PostRepository $postRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(PostRepository $postRepository, EntityManagerInterface $entityManager)
    {
        $this->postRepository = $postRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/posts', name: 'get_all_posts', methods: ['GET'])]
    public function getAllPosts(): JsonResponse
    {
        $posts = $this->postRepository->findAllPosts();

        $data = [];
        foreach ($posts as $post) {
            $data[] = [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'user' => $post->getUser()->getUsername()
            ];
        }

        return $this->json($data);
    }

    #[Route('/post/{id}', name: 'get_post', methods: ['GET'])]
    public function getPost(int $id): JsonResponse
    {
        $post = $this->postRepository->findPostById($id);

        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        return $this->json([
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'user' => $post->getUser()->getUsername(),
        ]);
    }

    #[Route('/post', name: 'create_post', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $title = $data['title'] ?? null;
        $content = $data['content'] ?? null;
        $userId = $data['userId'] ?? null;

        if (empty($title) || empty($content) || empty($userId)) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $post = new Post();
        $post->setTitle($title)
            ->setContent($content)
            ->setUser($user);

        $this->postRepository->save($post);

        return $this->json([
            'message' => 'Post created successfully',
            'post' => [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'user' => $user->getUsername(),
            ]
        ], 201);
    }

    #[Route('/post/{id}', name: 'update_post', methods: ['PUT'])]
    public function updatePost(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $title = $data['title'] ?? null;
        $content = $data['content'] ?? null;

        $post = $this->postRepository->findPostById($id);

        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        if ($title) {
            $post->setTitle($title);
        }

        if ($content) {
            $post->setContent($content);
        }

        $this->postRepository->save($post);

        return $this->json([
            'message' => 'Post updated successfully',
            'post' => [
                'id' => $post->getId(),
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'user' => $post->getUser()->getUsername(),
            ]
        ]);
    }
}
