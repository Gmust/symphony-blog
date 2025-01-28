<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Service\PostService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PostController extends AbstractController
{
    private PostService $postService;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(
        PostService $postService,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    )
    {
        $this->postService = $postService;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('/api/posts', name: 'api_get_all_posts', methods: ['GET'])]
    public function apiGetAllPosts(): JsonResponse
    {
        $posts = $this->postService->getAllPosts();
        $jsonContent = $this->serializer->serialize($posts, 'json', ['groups' => 'post:read']);
        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/api/posts/{id}', name: 'api_get_post', methods: ['GET'])]
    public function apiGetPost(int $id): JsonResponse
    {
        $post = $this->postService->getPost($id);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $this->serializer->serialize($post, 'json', ['groups' => 'post:read']);
        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/api/posts', name: 'api_create_post', methods: ['POST'])]
    public function apiCreatePost(Request $request, SessionInterface $session): JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['title']) || !isset($data['content'])) {
            return new JsonResponse(['message' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $post = new Post();
        $post->setTitle($data['title']);
        $post->setContent($data['content']);
        $post->setUser($user);

        $this->postService->savePost($post);

        $jsonContent = $this->serializer->serialize($post, 'json', ['groups' => 'post:read']);
        return new JsonResponse($jsonContent, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/posts/{id}', name: 'api_update_post', methods: ['PUT', 'PATCH'])]
    public function apiUpdatePost(int $id, Request $request): JsonResponse
    {
        $post = $this->postService->getPost($id);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        if ($post->getUser() !== $this->getUser()) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $post->setTitle($data['title'] ?? $post->getTitle());
        $post->setContent($data['content'] ?? $post->getContent());

        $this->postService->savePost($post);

        $jsonContent = $this->serializer->serialize($post, 'json', ['groups' => 'post:read']);
        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/api/posts/{id}', name: 'api_delete_post', methods: ['DELETE'])]
    public function apiDeletePost(int $id): JsonResponse
    {
        $post = $this->postService->getPost($id);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isGranted('ROLE_ADMIN') && $post->getUser() !== $this->getUser()) {
            return new JsonResponse(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->postService->deletePost($post);

        return new JsonResponse(['message' => 'Post deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
