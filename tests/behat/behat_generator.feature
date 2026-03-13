@tool @tool_mucatalog @javascript @MuTMS
Feature: Behat tool_mucatalog generator usage
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
      | student1  | Student   | 1         | student1@example.com |
    And the following "roles" exist:
      | name           | shortname |
      | Section viewer | pviewer   |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/mucatalog:view              | Allow      | pviewer  | System       |           |
      | moodle/site:configview           | Allow      | pviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | viewer1   | pviewer      | System       |           |

  Scenario: tool_mucatalog generator may create sections
    When the following "tool_mucatalog > sections" exist:
      | name            |
      | Nice section 1  |
    And the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name            | guestvisible | uservisible | cohortvisible | status   | frontpagepriority |
      |              |           | Other section 2 |              |             |               | draft    |                   |
      |              |           | Other section 3 | 0            | 1           |               | archived |                   |
      | Category     | CAT3      | Other section 4 | 1            | 0           | CH1, CH2      | active   | 99                |
    And I log in as "viewer1"
    And I am on the "tool_mucatalog > All sections management" page
    Then the following should exist in the "reportbuilder-table" table:
      | Section name       | Items | Management category | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | Nice section 1     | 0     | System              |                     | No                | Yes                  |                     | Active         |
      | Other section 2    | 0     | System              |                     | No                | No                   |                     | Draft          |
      | Other section 3    | 0     | System              |                     | No                | Yes                  |                     | Archived       |
      | Other section 4    | 0     | Cat 3               | 99                  | Yes               | No                   | Cohort 1, Cohort 2  | Active         |

  Scenario: tool_mucatalog generator may create items
    Given the following "tool_mucatalog > sections" exist:
      | name             | uservisible |
      | Nice section 1   | 1           |
      | Other section 2  | 1           |
    When the following "tool_mucatalog > items" exist:
      | section        | type   | reference |
      | Nice section 1 | course | Course 1  |
    And the following "tool_mucatalog > items" exist:
      | section        | type   | reference | name      | hiddenbefore     | hiddenafter      | status |
      | Nice section 1 | course | Course 2  |           |                  |                  | active |
      | Nice section 1 | course | Course 3  | Course X3 | ## 2025-12-24 ## | ## 2035-01-01 ## | draft  |
    And I log in as "viewer1"

    And I am on the "Course 1" "tool_mucatalog > Item" page
    Then I should see "Course 1"
    And I should see "Course 1" in the "Course" definition list item
    And I should see "Yes" in the "Sync item name" definition list item
    And I should see "Not set" in the "Hidden before" definition list item
    And I should see "Not set" in the "Hidden after" definition list item
    And I should see "Active" in the "Item status" definition list item

    And I am on the "Course 2" "tool_mucatalog > Item" page
    Then I should see "Course 2"
    And I should see "Course 2" in the "Course" definition list item
    And I should see "Yes" in the "Sync item name" definition list item
    And I should see "Not set" in the "Hidden before" definition list item
    And I should see "Not set" in the "Hidden after" definition list item
    And I should see "Active" in the "Item status" definition list item

    And I am on the "Course X3" "tool_mucatalog > Item" page
    And I should see "Course 3" in the "Course" definition list item
    And I should see "No" in the "Sync item name" definition list item
    And I should see "24 December 2025" in the "Hidden before" definition list item
    And I should see "1 January 2035" in the "Hidden after" definition list item
    And I should see "Draft" in the "Item status" definition list item

  Scenario: tool_mucatalog generator may create collections
    When the following "tool_mucatalog > collections" exist:
      | name              |
      | Nice collection 1 |
    And the following "tool_mucatalog > collections" exist:
      | contextlevel | reference | name               | guestvisible | uservisible | cohortvisible | frontpagepriority |
      |              |           | Other collection 2 |              |             |               |                   |
      |              |           | Other collection 3 | 0            | 1           |               |                   |
      | Category     | CAT3      | Other collection 4 | 1            | 0           | CH1, CH2      | 88                |
    And I log in as "viewer1"
    And I am on the "tool_mucatalog > All collections management" page
    Then the following should exist in the "reportbuilder-table" table:
      | Collection name       | Items | Management category | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  |
      | Nice collection 1     | 0     | System              |                     | No                | Yes                  |                     |
      | Other collection 2    | 0     | System              |                     | No                | No                   |                     |
      | Other collection 3    | 0     | System              |                     | No                | Yes                  |                     |
      | Other collection 4    | 0     | Cat 3               | 88                  | Yes               | No                   | Cohort 1, Cohort 2  |

  Scenario: tool_mucatalog generator may add items to collection
    Given the following "tool_mucatalog > sections" exist:
      | name             |
      | Nice section 1   |
    When the following "tool_mucatalog > items" exist:
      | section        | type   | reference |
      | Nice section 1 | course | Course 1  |
      | Nice section 1 | course | Course 2  |
      | Nice section 1 | course | Course 3  |
    And the following "tool_mucatalog > collections" exist:
      | name               | guestvisible | uservisible | contextlevel | reference | frontpagepriority |
      | Some collection 1  | 1            | 1           |              |           |                   |

    When the following "tool_mucatalog > collection_items" exist:
      | collection        | item     |
      | Some collection 1 | Course 1 |
      | Some collection 1 | Course 2 |
    And I log in as "viewer1"
    And I am on the "Some collection 1" "tool_mucatalog > Collection" page
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name | Item type | Item status | Section name   | Section status |
      | Course 1  | Course    | Active      | Nice section 1 | Active         |
      | Course 2  | Course    | Active      | Nice section 1 | Active         |
    And I should not see "Course 3"
