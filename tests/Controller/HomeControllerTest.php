<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    private $sessionId = '2730f65b4b67fe89b0bf7d8aee203f7e';

    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/home');

// Check for the My Profile title
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('h1', 'My Profile');
    }

    public function testDelete()
    {
        $client = static::createClient();
        $client->request('POST', '/home/delete/1');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('/home'));
    }

    public function testApiGetAboutMeUnauthorized()
    {
        $client = static::createClient();
        $client->request('GET', '/api/home/about-me');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testApiGetAboutMeAuthorized()
    {
        $client = static::createClient([], [
            'PHPSESSID' => $this->sessionId,
        ]);
        $crawler = $client->request('GET', '/api/home/about-me');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testApiAddAboutMeUnauthorized()
    {
        $client = static::createClient();
        $client->request('POST', '/api/home/about-me', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['key' => 'hobby', 'value' => ['Reading', 'Traveling']]));

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testApiAddAboutMeAuthorized()
    {
        $client = static::createClient([], [
            'PHPSESSID' => $this->sessionId,
        ]);
        $client->request('POST', '/api/home/about-me', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['key' => 'hobby', 'value' => ['Reading', 'Traveling']]));

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testApiDeleteAboutMeUnauthorized()
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/home/about-me/1');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testApiDeleteAboutMeAuthorized()
    {
        $client = static::createClient([], [
            'PHPSESSID' => $this->sessionId,
        ]);
        $client->request('DELETE', '/api/home/about-me/1');

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    public function testApiUpdateUserUnauthorized()
    {
        $client = static::createClient();
        $client->request('PUT', '/api/user/update', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'new_username',
            'email' => 'new_email@example.com',
            'currentPassword' => 'current_password',
            'newPassword' => 'new_password'
        ]));

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testApiUpdateUserAuthorized()
    {
        $client = static::createClient([], [
            'PHPSESSID' => $this->sessionId,
        ]);
        $client->request('PUT', '/api/user/update', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'username' => 'new_username',
            'email' => 'new_email@example.com',
            'currentPassword' => 'current_password',
            'newPassword' => 'new_password'
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
    }
}
