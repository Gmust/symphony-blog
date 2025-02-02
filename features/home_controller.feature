# features/home_controller.feature

Feature: HomeController
    In order to manage my profile and about me section
    As a user
    I need to be able to view my profile, add key-value pairs, and delete key-value pairs

    Scenario: Viewing the profile page
        Given I am on "/home"
        Then I should see "My Profile"

    Scenario: Adding a key-value pair
        Given I am on "/home"
        When I fill in "keyValueStoreForm[key]" with "hobby"
        And I fill in "keyValueStoreForm[value]" with "Reading, Traveling"
        And I press "Add"
        Then I should see "hobby"
        And I should see "Reading, Traveling"

    Scenario: Deleting a key-value pair
        Given I am on "/home"
        When I follow "Delete"
        Then I should not see "Deleted Key"
