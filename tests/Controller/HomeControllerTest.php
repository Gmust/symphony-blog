<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Repository\KeyValueStoreRepository;
use App\Entity\User;
use App\Entity\KeyValueStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class HomeControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;
    private $keyValueStoreRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->keyValueStoreRepository = $this->entityManager->getRepository(KeyValueStore::class);
    }

    public function testIndexAsAuthenticatedUser()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/home');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', "My Profile");
    }

    public function testIndexAsGuest()
    {
        $this->client->request('GET', '/home');

        $this->assertResponseRedirects('/login');
    }

    public function testDeleteExistingKeyValueStore()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->client->loginUser($user);

        $keyValue = $this->keyValueStoreRepository->findOneBy(['user' => $user]);
        $this->client->request('GET', '/home/delete/' . $keyValue->getId());

        $this->assertResponseRedirects('/home');

        // Verify deletion
        $deletedEntry = $this->keyValueStoreRepository->find($keyValue->getId());
        $this->assertNull($deletedEntry);
    }

    public function testDeleteNonExistingKeyValueStore()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->client->loginUser($user);

        $this->client->request('GET', '/home/delete/9999');

        $this->assertResponseRedirects('/home');
    }

    private function simulateSession(User $user)
    {
        $session = self::$container->get('session');
        $session->set('user_id', $user->getId());
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testApiGetAboutMeAuthenticated()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->simulateSession($user);

        $this->client->request('GET', '/api/home/about-me');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($data);
        $this->assertEquals('name', $data[0]['key']); // Adjust based on your fixture data
    }

    public function testApiGetAboutMeUnauthenticated()
    {
        $this->client->request('GET', '/api/home/about-me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testApiAddAboutMeAuthenticated()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->simulateSession($user);

        $data = [
            'key' => 'hobby',
            'value' => ['Coding', 'Music']
        ];

        $this->client->request(
            'POST',
            '/api/home/about-me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJson($this->client->getResponse()->getContent());

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('hobby', $responseData['key']);
    }

    public function testApiAddAboutMeInvalidData()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->simulateSession($user);

        $data = []; // Missing 'key' and 'value'

        $this->client->request(
            'POST',
            '/api/home/about-me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testApiDeleteAboutMeAuthenticated()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->simulateSession($user);

        $keyValue = $this->keyValueStoreRepository->findOneBy(['user' => $user]);

        $this->client->request('DELETE', '/api/home/about-me/' . $keyValue->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Verify deletion
        $deletedEntry = $this->keyValueStoreRepository->find($keyValue->getId());
        $this->assertNull($deletedEntry);
    }

    public function testApiDeleteAboutMeUnauthorized()
    {
        $this->client->request('DELETE', '/api/home/about-me/1');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testApiUpdateUserAuthenticated()
    {
        $user = $this->userRepository->findOneByEmail('user1@example.com');
        $this->simulateSession($user);

        $data = [
            'username' => 'UpdatedUser1',
            'email' => 'updateduser1@example.com',
            'currentPassword' => 'Password1',
            'newPassword' => 'NewPassword1'
        ];

        $this->client->request(
            'PUT',
            '/api/user/update',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $updatedUser = $this->userRepository->find($user->getId());
        $this->assertEquals('UpdatedUser1', $updatedUser->getUsername());
        $this->assertEquals('updateduser1@example.com', $updatedUser->getEmail());
    }

    public function testApiUpdateUserInvalidCurrentPassword()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->simulateSession($user);

        $data = [
            'currentPassword' => 'WrongPassword',
            'newPassword' => 'NewPassword1'
        ];

        $this->client->request(
            'PUT',
            '/api/user/update',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
