@javascript @demo
Feature: Install eZ Publish Demo with/without content
    As an anonymous user
    I need to be able to install eZ Publish Demo through Setup Wizard
    In order to interact with eZ Demo installation

    Scenario: Choose english UK as setup wizard language
        Given I am on the "Setup Wizard" page
        When I select "English (United Kingdom)"
        And I click at "Next" button
        Then I see "Finished" step
