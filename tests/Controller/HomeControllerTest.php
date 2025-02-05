<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Repository\KeyValueStoreRepository;
use App\Entity\User;
use App\Entity\KeyValueStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

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
        $this->assertNotNull($user, 'User not found.');

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


    public function testApiGetAboutMeUnauthenticated()
    {
        $this->client->request('GET', '/api/home/about-me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }


    public function testApiDeleteAboutMeUnauthorized()
    {
        $this->client->request('DELETE', '/api/home/about-me/1');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }


    public function testApiUpdateUserInvalidCurrentPassword()
    {
        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->assertNotNull($user, 'User not found.');

        $this->client->loginUser($user);

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

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
