// features/bootstrap/FeatureContext.php

<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

class FeatureContext extends MinkContext implements Context
{
    private $response;

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
        if ((int) $arg1 !== $actualStatusCode) {
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
}
