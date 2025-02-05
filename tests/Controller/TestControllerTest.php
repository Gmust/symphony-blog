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
        $this->assertSelectorTextContains('h1', "All Posts");
    }

    public function testGetPost()
    {
        $post = $this->postRepository->findOneById(15);
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

}
