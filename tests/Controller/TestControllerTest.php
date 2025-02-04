<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class TestControllerTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $postRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->postRepository = static::getContainer()->get(PostRepository::class);
    }

    /**
     * Helper method to simulate session authentication for API tests.
     */
    private function simulateSession(User $user)
    {
        $session = self::$container->get('session');
        $session->set('user_id', $user->getId());
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    // ### Functional Tests for HTML Endpoints ###

    public function testGetAllPosts()
    {
        $crawler = $this->client->request('GET', '/posts');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.post'); // Adjust the selector based on your template
    }

    public function testGetPost()
    {
        $post = $this->postRepository->findOneBy([]);
        $this->assertNotNull($post, 'No posts found in the database.');

        $this->client->request('GET', '/posts/' . $post->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $post->getTitle());
    }

    public function testGetPostNotFound()
    {
        $this->client->request('GET', '/posts/99999'); // Assuming ID 99999 doesn't exist

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreatePostAsAuthenticatedUser()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/post/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Create')->form([
            'post[title]' => 'Test Title',
            'post[content]' => 'Test content for the post.',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/posts');

        // Follow the redirect
        $this->client->followRedirect();

        // Verify the post was created
        $createdPost = $this->postRepository->findOneBy(['title' => 'Test Title']);
        $this->assertNotNull($createdPost);
        $this->assertEquals($user->getId(), $createdPost->getUser()->getId());
    }

    public function testCreatePostAsAnonymous()
    {
        $this->client->request('GET', '/post/new');

        $this->assertResponseRedirects('/login');
    }

    public function testUpdatePostAsOwner()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->client->loginUser($user);

        $post = $this->postRepository->findOneBy(['user' => $user]);
        $this->assertNotNull($post, 'No post found for the user.');

        $crawler = $this->client->request('GET', '/post/' . $post->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Update')->form([
            'post[title]' => 'Updated Title',
            'post[content]' => 'Updated content.',
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/posts');

        // Follow the redirect
        $this->client->followRedirect();

        // Verify the post was updated
        $updatedPost = $this->postRepository->find($post->getId());
        $this->assertEquals('Updated Title', $updatedPost->getTitle());
    }

    public function testUpdatePostAsNonOwner()
    {
        $user = $this->userRepository->findOneByEmail('user2@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->client->loginUser($user);

        // Get a post not owned by this user
        $post = $this->postRepository->findOneByUserNot($user);
        $this->assertNotNull($post, 'No post found not owned by the user.');

        $this->client->request('GET', '/post/' . $post->getId() . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeletePostAsOwner()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->client->loginUser($user);

        $post = $this->postRepository->findOneBy(['user' => $user]);
        $this->assertNotNull($post, 'No post found for the user.');

        // Fetch CSRF token
        $crawler = $this->client->request('GET', '/posts');
        $this->assertResponseIsSuccessful();

        $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('delete' . $post->getId());

        $this->client->request('POST', '/post/' . $post->getId() . '/delete', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/posts');

        // Follow the redirect
        $this->client->followRedirect();

        // Verify the post was deleted
        $deletedPost = $this->postRepository->find($post->getId());
        $this->assertNull($deletedPost);
    }

    public function testDeletePostAsNonOwner()
    {
        $user = $this->userRepository->findOneByEmail('user2@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->client->loginUser($user);

        // Get a post not owned by this user
        $post = $this->postRepository->findOneByUserNot($user);
        $this->assertNotNull($post, 'No post found not owned by the user.');

        // Fetch CSRF token
        $crawler = $this->client->request('GET', '/posts');
        $this->assertResponseIsSuccessful();

        $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('delete' . $post->getId());

        $this->client->request('POST', '/post/' . $post->getId() . '/delete', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseStatusCodeSame(403);

        // Verify the post was not deleted
        $existingPost = $this->postRepository->find($post->getId());
        $this->assertNotNull($existingPost);
    }

    // ### Tests for API Endpoints ###

    public function testApiGetAllPosts()
    {
        $this->client->request('GET', '/api/posts');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function testApiGetPost()
    {
        $post = $this->postRepository->findOneBy([]);
        $this->assertNotNull($post, 'No posts found in the database.');

        $this->client->request('GET', '/api/posts/' . $post->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($post->getId(), $data['id']);
    }

    public function testApiGetPostNotFound()
    {
        $this->client->request('GET', '/api/posts/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Post not found', $data['message']);
    }

    public function testApiCreatePostAuthorized()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->simulateSession($user);

        $data = [
            'title' => 'API Test Title',
            'content' => 'Content from API test.',
        ];

        $this->client->request(
            'POST',
            '/api/posts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('API Test Title', $responseData['title']);
        $this->assertEquals($user->getId(), $responseData['user']['id']);
    }

    public function testApiCreatePostUnauthorized()
    {
        $data = [
            'title' => 'API Test Title',
            'content' => 'Content from API test.',
        ];

        $this->client->request(
            'POST',
            '/api/posts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testApiUpdatePostAuthorized()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->simulateSession($user);

        $post = $this->postRepository->findOneBy(['user' => $user]);
        $this->assertNotNull($post, 'No post found for the user.');

        $data = [
            'title' => 'Updated API Title',
            'content' => 'Updated content from API test.',
        ];

        $this->client->request(
            'PUT',
            '/api/posts/' . $post->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseIsSuccessful();

        $updatedPost = $this->postRepository->find($post->getId());
        $this->assertEquals('Updated API Title', $updatedPost->getTitle());
    }

    public function testApiUpdatePostUnauthorized()
    {
        $user = $this->userRepository->findOneByEmail('user2@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->simulateSession($user);

        // Get a post not owned by this user
        $post = $this->postRepository->findOneByUserNot($user);
        $this->assertNotNull($post, 'No post found not owned by the user.');

        $data = [
            'title' => 'Hacked Title',
            'content' => 'Hacked content.',
        ];

        $this->client->request(
            'PUT',
            '/api/posts/' . $post->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Verify the post was not updated
        $existingPost = $this->postRepository->find($post->getId());
        $this->assertNotEquals('Hacked Title', $existingPost->getTitle());
    }

    public function testApiDeletePostAuthorized()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->simulateSession($user);

        $post = $this->postRepository->findOneBy(['user' => $user]);
        $this->assertNotNull($post, 'No post found for the user.');

        $this->client->request('DELETE', '/api/posts/' . $post->getId());

        $this->assertResponseIsSuccessful();

        // Verify the post was deleted
        $deletedPost = $this->postRepository->find($post->getId());
        $this->assertNull($deletedPost);
    }

    public function testApiDeletePostUnauthorized()
    {
        $user = $this->userRepository->findOneByEmail('user2@example.com');
        $this->assertNotNull($user, 'User not found.');
        $this->simulateSession($user);

        // Get a post not owned by this user
        $post = $this->postRepository->findOneByUserNot($user);
        $this->assertNotNull($post, 'No post found not owned by the user.');

        $this->client->request('DELETE', '/api/posts/' . $post->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        // Verify the post was not deleted
        $existingPost = $this->postRepository->find($post->getId());
        $this->assertNotNull($existingPost);
    }
}
