<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Service\PostService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PostController extends AbstractController
{
    private PostRepository $postRepository;
    private PostService $postService;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(
        PostRepository         $postRepository,
        PostService            $postService,
        EntityManagerInterface $entityManager,
        SerializerInterface    $serializer,
    )
    {
        $this->postRepository = $postRepository;
        $this->postService = $postService;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    // HTML Endpoints
    #[Route('/posts', name: 'get_all_posts', methods: ['GET'])]
    public function getAllPosts(): Response
    {
        $posts = $this->postRepository->findAllPosts();
        return $this->render('posts/index.html.twig', ['posts' => $posts]);
    }

    #[Route('/posts/{id}', name: 'get_post', methods: ['GET'])]
    public function getPost(int $id): Response
    {
        $post = $this->postRepository->findPostById($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        return $this->render('posts/show.html.twig', ['post' => $post]);
    }

    #[Route('/post/new', name: 'create_post', methods: ['GET', 'POST'])]
    public function createPost(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $this->postRepository->save($post);
            return $this->redirectToRoute('get_all_posts');
        }

        return $this->render('posts/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/post/{id}/edit', name: 'update_post', methods: ['GET', 'POST'])]
    public function updatePost(int $id, Request $request): Response
    {
        $post = $this->postRepository->findPostById($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Authorization check
        if ($post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to edit this post.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->postRepository->save($post);
            return $this->redirectToRoute('get_all_posts');
        }

        return $this->render('posts/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/post/{id}/delete', name: 'delete_post', methods: ['POST'])]
    public function deletePost(int $id, Request $request, AuthorizationCheckerInterface $authChecker): Response
    {
        $post = $this->postRepository->findPostById($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        // Authorization check
        if (!$authChecker->isGranted('ROLE_ADMIN') && $post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to delete this post.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $this->postRepository->delete($post);
        }

        return $this->redirectToRoute('get_all_posts');
    }

    // API Endpoints
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
    public function apiUpdatePost(int $id, Request $request, SessionInterface $session): JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $this->postService->getPost($id);

        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $post->setTitle($data['title'] ?? $post->getTitle());
        $post->setContent($data['content'] ?? $post->getContent());

        $this->postService->savePost($post);

        $jsonContent = $this->serializer->serialize($post, 'json', ['groups' => 'post:read']);
        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    #[Route('/api/posts/{id}', name: 'api_delete_post', methods: ['DELETE'])]
    public function apiDeletePost(int $id, Request $request, SessionInterface $session): JsonResponse
    {
        $userId = $session->get('user_id');
        if (!$userId) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $post = $this->postRepository->findPostById($id);
        if (!$post) {
            return new JsonResponse(['message' => 'Post not found'], Response::HTTP_NOT_FOUND);
        }

        // Retrieve the user from the session
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Authorization check
        if ($user->getId() !== $post->getUser()->getId() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Delete post
        $this->postRepository->delete($post);

        // Fetch all remaining posts
        $posts = $this->postRepository->findAllPosts();
        $jsonContent = $this->serializer->serialize($posts, 'json', ['groups' => 'post:read']);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

}

