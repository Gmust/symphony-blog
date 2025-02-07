<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Transformer\PostTranformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class PostService
{
    private PostRepository $postRepository;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private PostTranformer $postTransformer;

    public function __construct(PostRepository $postRepository, CsrfTokenManagerInterface $csrfTokenManager, PostTranformer $postTransformer)
    {
        $this->postRepository = $postRepository;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->postTransformer = $postTransformer;
    }

    public function getAllPosts()
    {
        $posts = $this->postRepository->findAllPosts();
        return array_map([$this->postTransformer, 'transform'], $posts);
    }

    public function getPost(int $id): ?array
    {
        $post = $this->postRepository->findPostById($id);
        if (!$post) {
            return null;
        }

        return $this->postTransformer->transform($post);
    }

    public function savePost(Post $post): void
    {
        $this->postRepository->save($post);
    }

    public function updatePost(int $id, array $data): ?Post
    {
        $post = $this->postRepository->findPostById($id);
        if (!$post) {
            return null;
        }

        $post = $this->postTransformer->reverseTransform($data, $post);
        $this->savePost($post);

        return $post;
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
