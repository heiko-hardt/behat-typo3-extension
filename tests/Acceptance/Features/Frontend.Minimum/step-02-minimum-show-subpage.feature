Feature: Frontend testing (minimum)
    In order to open the website
    As a frontend user
    I need to be able to read the typoscript content of a subpage

    Scenario: Show subpage and read content
        Given I am on "/index.php?id=2"
         Then I should see "Hello, subpage!"
