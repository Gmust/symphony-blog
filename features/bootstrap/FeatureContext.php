// features/bootstrap/FeatureContext.php

<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\KeyValueStore;

class FeatureContext extends MinkContext implements Context
{
    private $response;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @BeforeScenario
     */
    public function setUp(BeforeScenarioScope $scope)
    {
        $this->response = null;
    }

    /**
     * @Given I am on :arg1
     */
    public function iAmOn($arg1)
    {
        $this->visitPath($arg1);
    }

    /**
     * @When I fill in :arg1 with :arg2
     */
    public function iFillInWith($arg1, $arg2)
    {
        $this->fillField($arg1, $arg2);
    }

    /**
     * @When I press :arg1
     */
    public function iPress($arg1)
    {
        $this->pressButton($arg1);
    }

    /**
     * @When I follow :arg1
     */
    public function iFollow($arg1)
    {
        $this->clickLink($arg1);
    }

    /**
     * @Then I should see :arg1
     */
    public function iShouldSee($arg1)
    {
        $this->assertPageContainsText($arg1);
    }

    /**
     * @Then I should not see :arg1
     */
    public function iShouldNotSee($arg1)
    {
        $this->assertPageNotContainsText($arg1);
    }

    /**
     * @Then I should be on :arg1
     */
    public function iShouldBeOn($arg1)
    {
        $this->assertSession()->addressEquals($arg1);
    }

    /**
     * @When I send a JSON payload with:
     */
    public function iSendAJsonPayloadWith(TableNode $table)
    {
        $client = $this->getSession()->getDriver()->getClient();
        $data = [];
        foreach ($table->getRowsHash() as $key => $value) {
            $data[$key] = $value;
        }
        $this->response = $client->request('POST', $this->getSession()->getCurrentUrl(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($data));
    }

    /**
     * @Then the response status code should be :arg1
     */
    public function theResponseStatusCodeShouldBe($arg1)
    {
        $actualStatusCode = $this->response->getStatus();
        if ((int)$arg1 !== $actualStatusCode) {
            throw new \Exception("Expected status code $arg1, but got $actualStatusCode.");
        }
    }

    /**
     * @Then the response should contain :arg1
     */
    public function theResponseShouldContain($arg1)
    {
        $responseContent = $this->response->getContent();
        if (strpos($responseContent, $arg1) === false) {
            throw new \Exception("Expected response to contain '$arg1', but it did not.");
        }
    }

    /**
     * @Given there is a user with username :username and password :password
     */
    public function thereIsAUserWithUsernameAndPassword($username, $password)
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $user->setEmail("$username@example.com");

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @Given the user is logged in
     */
    public function theUserIsLoggedIn()
    {
        // Implement the login logic here to authenticate the user session
        $this->visitPath('/login');
        $this->fillField('username', 'john_doe');
        $this->fillField('password', 'password123');
        $this->pressButton('Login');
    }

    /**
     * @Given the following key-value pairs exist:
     */
    public function theFollowingKeyValuePairsExist(TableNode $table)
    {
        foreach ($table->getHash() as $row) {
            $keyValueStore = new KeyValueStore();
            $keyValueStore->setUser($this->entityManager->getRepository(User::class)->findOneByUsername('john_doe'));
            $keyValueStore->setKey($row['key']);
            $keyValueStore->setValue($row['value']);
            $this->entityManager->persist($keyValueStore);
        }
        $this->entityManager->flush();
    }

    /**
     * @Given the following posts exist:
     */
    public function theFollowingPostsExist(TableNode $table)
    {
        foreach ($table->getHash() as $row) {
            $post = new Post();
            $post->setTitle($row['title']);
            $post->setContent($row['content']);
            $post->setUser($this->entityManager->getRepository(User::class)->findOneByUsername('john_doe'));
            $this->entityManager->persist($post);
        }
        $this->entityManager->flush();

    }
}
