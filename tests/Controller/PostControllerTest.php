<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostControllerTest extends WebTestCase
{
private $sessionId = '2730f65b4b67fe89b0bf7d8aee203f7e';

public function testGetAllPosts()
{
$client = static::createClient();
$crawler = $client->request('GET', '/posts');

$this->assertEquals(200, $client->getResponse()->getStatusCode());
$this->assertSelectorTextContains('h1', 'Posts');
}

public function testGetPost()
{
$client = static::createClient();
$crawler = $client->request('GET', '/posts/1');

$this->assertEquals(200, $client->getResponse()->getStatusCode());
$this->assertSelectorTextContains('h1', 'Post');
}

public function testCreatePost()
{
$client = static::createClient([], [
'PHPSESSID' => $this->sessionId,
]);
$crawler = $client->request('GET', '/post/new');

// Ensure the form exists
$this->assertGreaterThan(0, $crawler->filter('form')->count());

$form = $crawler->selectButton('Save')->form();
$form['post[title]'] = 'New Post';
$form['post[content]'] = 'This is a new post.';
$client->submit($form);

$this->assertTrue($client->getResponse()->isRedirect('/posts'));
$client->followRedirect();

$this->assertSelectorTextContains('.post-title', 'New Post');
}

public function testUpdatePost()
{
$client = static::createClient([], [
'PHPSESSID' => $this->sessionId,
]);
$crawler = $client->request('GET', '/post/1/edit');

// Ensure the form exists
$this->assertGreaterThan(0, $crawler->filter('form')->count());

$form = $crawler->selectButton('Save')->form();
$form['post[title]'] = 'Updated Post';
$form['post[content]'] = 'This is an updated post.';
$client->submit($form);

$this->assertTrue($client->getResponse()->isRedirect('/posts'));
$client->followRedirect();

$this->assertSelectorTextContains('.post-title', 'Updated Post');
}

public function testDeletePost()
{
$client = static::createClient([], [
'PHPSESSID' => $this->sessionId,
]);
$crawler = $client->request('GET', '/posts');

// Ensure the delete button exists
$this->assertGreaterThan(0, $crawler->selectButton('Delete')->count());

$form = $crawler->selectButton('Delete')->form();
$client->submit($form);

$this->assertTrue($client->getResponse()->isRedirect('/posts'));
$client->followRedirect();

$this->assertSelectorTextNotContains('.post-title', 'Deleted Post');
}

public function testApiGetAllPosts()
{
$client = static::createClient();
$client->request('GET', '/api/posts');

$this->assertEquals(200, $client->getResponse()->getStatusCode());
$this->assertJson($client->getResponse()->getContent());
}

public function testApiGetPost()
{
$client = static::createClient();
$client->request('GET', '/api/posts/1');

$this->assertEquals(200, $client->getResponse()->getStatusCode());
$this->assertJson($client->getResponse()->getContent());
}

public function testApiCreatePost()
{
$client = static::createClient([], [
'PHPSESSID' => $this->sessionId,
]);
$client->request('POST', '/api/posts', [], [], [
'CONTENT_TYPE' => 'application/json',
], json_encode(['title' => 'API Post', 'content' => 'This is an API post.']));

$this->assertEquals(201, $client->getResponse()->getStatusCode());
$this->assertJson($client->getResponse()->getContent());
}

public function testApiUpdatePost()
{
$client = static::createClient([], [
'PHPSESSID' => $this->sessionId,
]);
$client->request('PUT', '/api/posts/1', [], [], [
'CONTENT_TYPE' => 'application/json',
], json_encode(['title' => 'Updated API Post', 'content' => 'This is an updated API post.']));

$this->assertEquals(200, $client->getResponse()->getStatusCode());
$this->assertJson($client->getResponse()->getContent());
}

public function testApiDeletePost()
{
$client = static::createClient([], [
'PHPSESSID' => $this->sessionId,
]);
$client->request('DELETE', '/api/posts/1');

$this->assertEquals(204, $client->getResponse()->getStatusCode());
}
}
