# features/home_controller.feature

Feature: HomeController
    In order to manage my profile and about me section
    As a user
    I need to be able to view my profile, add key-value pairs, and delete key-value pairs

    Scenario: Viewing the profile page
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        Given I am on "/home"
        Then I should see "My Profile"

    Scenario: Adding a key-value pair
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        And I am on "/home"
        When I fill in "keyValueStoreForm[key]" with "hobby"
        And I fill in "keyValueStoreForm[value]" with "Reading, Traveling"
        And I press "Add"
        Then I should see "hobby"
        And I should see "Reading, Traveling"

    Scenario: Deleting a key-value pair
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        And the following key-value pairs exist:
            | key     | value              |
            | hobby   | Reading, Traveling |
        And I am on "/home"
        When I follow "Delete"
        Then I should not see "hobby"

    Scenario: API - Adding a key-value pair
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        When I send a JSON payload with:
            | key   | value                |
            | hobby | Reading, Traveling   |
        Then the response status code should be 201
        And the response should contain "hobby"
        And the response should contain "Reading, Traveling"
