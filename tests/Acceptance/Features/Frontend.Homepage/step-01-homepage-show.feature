Feature: Frontend testing
    In order to open the website
    As a frontend user
    I need to be able to read the typoscript content on the homepage

    Scenario: Show homepage and read content
        Given I am on homepage
         Then I should see "Hello, world!"
