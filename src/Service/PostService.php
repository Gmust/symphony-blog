<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class PostService
{
    private PostRepository $postRepository;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(PostRepository $postRepository, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->postRepository = $postRepository;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function getAllPosts()
    {
        return $this->postRepository->findAllPosts();
    }

    public function getPost(int $id): ?Post
    {
        return $this->postRepository->findPostById($id);
    }

    public function savePost(Post $post)
    {
        $this->postRepository->save($post);
    }

    public function deletePost(Post $post, AuthorizationCheckerInterface $authChecker, Request $request): bool
    {
        if (!$authChecker->isGranted('ROLE_ADMIN') && $post->getUser() !== $authChecker->getUser()) {
            throw new AccessDeniedException('You do not have permission to delete this post.');
        }

        $csrfToken = new CsrfToken('delete' . $post->getId(), $request->request->get('_token'));
        if ($this->csrfTokenManager->isTokenValid($csrfToken)) {
            $this->postRepository->delete($post);
            return true;
        }

        return false;
    }
}
