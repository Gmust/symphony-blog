<?php

namespace App\Tests\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Service\PostService;
use App\Transformer\PostTranformer;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class PostServiceTest extends TestCase
{
    private $postRepository;
    private $csrfTokenManager;
    private $postTransformer;
    private $postService;
    private $authChecker;
    private $request;

    protected function setUp(): void
    {
        $this->postRepository = Mockery::mock(PostRepository::class);
        $this->csrfTokenManager = Mockery::mock(CsrfTokenManagerInterface::class);
        $this->postTransformer = Mockery::mock(PostTranformer::class);
        $this->authChecker = Mockery::mock(AuthorizationCheckerInterface::class);
        $this->request = new Request();

        $this->postService = new PostService(
            $this->postRepository,
            $this->csrfTokenManager,
            $this->postTransformer
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetAllPosts()
    {
        $post = new Post();
        $posts = [$post];
        $transformedPost = ['id' => 1, 'title' => 'Test Post', 'content' => 'Test Content'];

        $this->postRepository
            ->shouldReceive('findAllPosts')
            ->once()
            ->andReturn($posts);

        $this->postTransformer
            ->shouldReceive('transform')
            ->once()
            ->with($post)
            ->andReturn($transformedPost);

        $result = $this->postService->getAllPosts();

        $this->assertEquals([$transformedPost], $result);
    }

    public function testGetPost()
    {
        $post = new Post();
        $transformedPost = ['id' => 1, 'title' => 'Test Post', 'content' => 'Test Content'];

        $this->postRepository
            ->shouldReceive('findPostById')
            ->once()
            ->with(1)
            ->andReturn($post);

        $this->postTransformer
            ->shouldReceive('transform')
            ->once()
            ->with($post)
            ->andReturn($transformedPost);

        $result = $this->postService->getPost(1);

        $this->assertEquals($transformedPost, $result);
    }

    public function testSavePost()
    {
        $post = new Post();

        $this->postRepository
            ->shouldReceive('save')
            ->once()
            ->with($post);

        $this->postService->savePost($post);
    }

    public function testUpdatePost()
    {
        $post = new Post();
        $data = ['title' => 'Updated Title', 'content' => 'Updated Content'];

        $this->postRepository
            ->shouldReceive('findPostById')
            ->once()
            ->with(1)
            ->andReturn($post);

        $this->postTransformer
            ->shouldReceive('reverseTransform')
            ->once()
            ->with($data, $post)
            ->andReturn($post);

        $this->postRepository
            ->shouldReceive('save')
            ->once()
            ->with($post);

        $result = $this->postService->updatePost(1, $data);

        $this->assertEquals($post, $result);
    }

    public function testDeletePost()
    {
        $post = Mockery::mock(Post::class);
        $csrfToken = new CsrfToken('delete1', 'valid_token');

        $post->shouldReceive('getId')->andReturn(1);
        $post->shouldReceive('getUser')->andReturn('user');

        $this->authChecker
            ->shouldReceive('isGranted')
            ->once()
            ->with('ROLE_ADMIN')
            ->andReturn(true);

        $this->request->request->set('_token', 'valid_token');

        $this->csrfTokenManager
            ->shouldReceive('isTokenValid')
            ->once()
            ->with(Mockery::on(function (CsrfToken $token) {
                return $token->getValue() === 'valid_token' && $token->getId() === 'delete1';
            }))
            ->andReturn(true);

        $this->postRepository
            ->shouldReceive('delete')
            ->once()
            ->with($post);

        $result = $this->postService->deletePost($post, $this->authChecker, $this->request);

        $this->assertTrue($result);
    }

    public function testDeletePostAccessDenied()
    {
        $post = Mockery::mock(Post::class);
        $post->shouldReceive('getUser')->andReturn('another_user');

        $this->authChecker
            ->shouldReceive('isGranted')
            ->once()
            ->with('ROLE_ADMIN')
            ->andReturn(false);

        $this->expectException(AccessDeniedException::class);

        $this->postService->deletePost($post, $this->authChecker, $this->request);
    }
}
