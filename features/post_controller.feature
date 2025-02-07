# features/post_controller.feature

Feature: PostController
    In order to manage posts
    As a user
    I need to be able to create, update, view, and delete posts

    Scenario: Viewing all posts
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        Given I am on "/posts"
        Then I should see "Posts"

    Scenario: Viewing a single post
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        Given the following posts exist:
            | title     | content              |
            | First Post| This is the first post |
        Given I am on "/posts/1"
        Then I should see "Post"

    Scenario: Creating a new post
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        Given I am on "/post/new"
        When I fill in "post[title]" with "New Post"
        And I fill in "post[content]" with "This is a new post."
        And I press "Save"
        Then I should be on "/posts"
        And I should see "New Post"

    Scenario: Editing a post
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        And the following posts exist:
            | title     | content              |
            | Old Post  | This is an old post   |
        Given I am on "/post/1/edit"
        When I fill in "post[title]" with "Updated Post"
        And I fill in "post[content]" with "This is an updated post."
        And I press "Save"
        Then I should be on "/posts"
        And I should see "Updated Post"

    Scenario: Deleting a post
        Given there is a user with username "john_doe" and password "password123"
        And the user is logged in
        And the following posts exist:
            | title       | content              |
            | Deleted Post| This post will be deleted |
        Given I am on "/posts"
        When I press "Delete"
        Then I should be on "/posts"
        And I should not see "Deleted Post"
