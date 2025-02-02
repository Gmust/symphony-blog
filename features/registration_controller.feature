# features/registration_controller.feature

Feature: RegistrationController
    In order to register new users
    As a visitor
    I need to be able to register via the web interface and the API

    Scenario: Registering a new user via the web interface
        Given I am on "/register"
        When I fill in "registrationForm[username]" with "newuser"
        And I fill in "registrationForm[email]" with "newuser@example.com"
        And I fill in "registrationForm[plainPassword]" with "password"
        And I press "Register"
        Then I should be on "/home"
        And I should see "My Profile"

    Scenario: Registering a new user via the API
        Given I am on "/api/register"
        When I send a JSON payload with:
            | username | newuser |
            | email    | newuser@example.com |
            | password | password |
        Then the response status code should be 201
        And the response should contain "User registered successfully"
