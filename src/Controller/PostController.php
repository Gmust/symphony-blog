<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Service\PostService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PostController extends AbstractController
{
    private PostService $postService;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(PostService $postService, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->postService = $postService;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    #[Route('/posts', name: 'get_all_posts', methods: ['GET'])]
    public function getAllPosts(): Response
    {
        $posts = $this->postService->getAllPosts();
        return $this->render('posts/index.html.twig', ['posts' => $posts]);
    }

    #[Route('/posts/{id}', name: 'get_post', methods: ['GET'])]
    public function getPost(int $id): Response
    {
        $post = $this->postService->getPost($id);

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
            $this->postService->savePost($post);
            return $this->redirectToRoute('get_all_posts');
        }

        return $this->render('posts/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/post/{id}/edit', name: 'update_post', methods: ['GET', 'POST'])]
    public function updatePost(int $id, Request $request): Response
    {
        $post = $this->postService->getPost($id);

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
            $this->postService->savePost($post);
            return $this->redirectToRoute('get_all_posts');
        }

        return $this->render('posts/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/post/{id}/delete', name: 'delete_post', methods: ['POST'])]
    public function deletePost(int $id, Request $request, AuthorizationCheckerInterface $authChecker): Response
    {
        $post = $this->postService->getPost($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        if ($this->postService->deletePost($post, $authChecker, $request)) {
            return $this->redirectToRoute('get_all_posts');
        }

        return $this->createAccessDeniedException('Could not delete post.');
    }
}
