@tool @tool_mucatalog @javascript @MuTMS
Feature: tool_mucatalog navigation behat steps test
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
      | Course 3 | C3        |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference |
      | Cohort 1   | CH1      | System       |           |
      | Cohort 2   | CH2      | System       |           |
      | Cohort 3   | CH3      | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | viewer1   | Viewer    | 1         | viewer1@example.com  |
      | viewer2   | Viewer    | 2         | viewer2@example.com  |
      | student1  | Student   | 1         | student1@example.com |
    And the following "roles" exist:
      | name         | shortname |
      | Page viewer  | pviewer   |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/mucatalog:view              | Allow      | pviewer  | System       |           |
      | moodle/site:configview           | Allow      | pviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | viewer1   | pviewer      | System       |           |
      | viewer2   | pviewer      | Category     | CAT2      |
    And the following "tool_mucatalog > sections" exist:
      | name            | status   | guestvisible | uservisible | contextlevel | reference | frontpagepriority |
      | Some section 1  | active   | 1            | 1           |              |           | 10                |
      | Other section 2 | draft    | 0            | 0           | Category     | CAT2      |                   |
      | Other section 3 | active   | 1            | 1           | Category     | CAT3      |                   |
    And the following "tool_mucatalog > items" exist:
      | section        | type   | reference |
      | Some section 1 | course | Course 1  |
      | Some section 1 | course | Course 2  |
    And the following "tool_mucatalog > collections" exist:
      | name               | guestvisible | uservisible | contextlevel | reference | frontpagepriority |
      | Some collection 1  | 1            | 1           |              |           |                   |
      | Other collection 2 | 0            | 0           | Category     | CAT2      |                   |
      | Other collection 3 | 1            | 1           | Category     | CAT3      | -10               |
    And the following "tool_mucatalog > collection_items" exist:
      | collection        | item     |
      | Some collection 1 | Course 1 |

  Scenario: System viewer navigates to All sections management via behat step
    Given I log in as "viewer1"

    When I am on the "tool_mucatalog > All sections management" page
    Then I should see "Section management"
    And the following should exist in the "reportbuilder-table" table:
      | Section name       | Management category | Section status |
      | Some section 1     | System              | Active         |
      | Other section 2    | Cat 2               | Draft          |
      | Other section 3    | Cat 3               | Active         |

  Scenario: Category viewer navigates to Sections management via behat step
    Given I log in as "viewer2"

    When I am on the "Cat 2" "tool_mucatalog > Sections management" page
    Then I should see "Section management"
    And the following should exist in the "reportbuilder-table" table:
      |Section name     | Management category | Section status |
      |Other section 2  | Cat 2               | Draft          |
      |Other section 3  | Cat 3               | Active         |
    And I should not see "Some section 1"

  Scenario: Category viewer navigates to Sections management the normal way
    Given I log in as "admin"
    And I set the following administration settings values:
      | Site home items when logged in | List of categories |
    And I log out

    And I log in as "viewer2"
    And I click on "Home" "link" in the ".primary-navigation" "css_element"
    And I follow "Cat 2"
    And I click on "More" "link" in the ".secondary-navigation" "css_element"

    When I click on "Section management" "link" in the ".secondary-navigation" "css_element"
    Then I should see "Section management"
    And the following should exist in the "reportbuilder-table" table:
      |Section name     | Management category | Section status |
      |Other section 2  | Cat 2               | Draft          |
      |Other section 3  | Cat 3               | Active         |
    And I should not see "Some section 1"

  Scenario: System viewer navigates to All collections management via behat step
    Given I log in as "viewer1"

    When I am on the "tool_mucatalog > All collections management" page
    Then I should see "Collection management"
    And the following should exist in the "reportbuilder-table" table:
      | Collection name       | Management category |
      | Some collection 1     | System              |
      | Other collection 2    | Cat 2               |
      | Other collection 3    | Cat 3               |

  Scenario: Category viewer navigates to Collections management via behat step
    Given I log in as "viewer2"

    When I am on the "Cat 2" "tool_mucatalog > Collections management" page
    Then I should see "Collection management"
    And the following should exist in the "reportbuilder-table" table:
      | Collection name     | Management category |
      | Other collection 2  | Cat 2               |
      | Other collection 3  | Cat 3               |
    And I should not see "Some collection 1"

  Scenario: Category viewer navigates to Collections management the normal way
    Given I log in as "admin"
    And I set the following administration settings values:
      | Site home items when logged in | List of categories |
    And I log out

    And I log in as "viewer2"
    And I click on "Home" "link" in the ".primary-navigation" "css_element"
    And I follow "Cat 2"
    And I click on "More" "link" in the ".secondary-navigation" "css_element"

    When I click on "Section management" "link" in the ".secondary-navigation" "css_element"
    And I click on "Collection management" action from "Catalogue actions" dropdown

    Then I should see "Collection management"
    And the following should exist in the "reportbuilder-table" table:
      |Collection name     | Management category |
      |Other collection 2  | Cat 2               |
      |Other collection 3  | Cat 3               |
    And I should not see "Some collection 1"

  Scenario: Viewer navigates to Section management via behat step
    Given I log in as "viewer1"

    When I am on the "Other section 2" "tool_mucatalog > Section" page
    Then I should see "Other section 2"
    And I should see "Cat 2" in the "Management category" definition list item
    And I should see "Draft" in the "Section status" definition list item

  Scenario: Viewer navigates to Collection management via behat step
    Given I log in as "viewer1"

    When I am on the "Other collection 3" "tool_mucatalog > Collection" page
    Then I should see "Other collection 3"
    And I should see "Cat 3" in the "Management category" definition list item
    And I should see "-10" in the "Front page priority" definition list item

  Scenario: Viewer navigates to Item management via behat step
    Given I log in as "viewer1"

    When I am on the "Course 1" "tool_mucatalog > Item" page
    Then I should see "Course 1"
    And I should see "Course 1" in the "Course" definition list item
    And I should see "Yes" in the "Sync item name" definition list item
    And I should see "Not set" in the "Hidden before" definition list item
    And I should see "Not set" in the "Hidden after" definition list item
    And I should see "Active" in the "Item status" definition list item

  Scenario: Student navigates to Catalogue Frontpage via behat step
    Given I log in as "student1"

    When I am on the "tool_mucatalog > Catalogue Frontpage" page
    Then I should see "Some section 1"
    And I should see "All items"
    And I should see "Other collection 3"

  Scenario: Student navigates to Catalogue Frontpage the normal way
    Given I log in as "student1"

    When I click on "Catalogue" "link" in the ".primary-navigation" "css_element"

    Then I should see "Some section 1"
    And I should see "All items"
    And I should see "Other collection 3"

  Scenario: Student navigates to Catalogue All Items via behat step
    Given I log in as "student1"

    When I am on the "tool_mucatalog > Catalogue All Items" page

    Then I should see "Course 1"
    And I should see "Course 2"
    And I should not see "Course 3"
