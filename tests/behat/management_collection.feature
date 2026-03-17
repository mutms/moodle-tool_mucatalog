@tool @tool_mucatalog @javascript @MuTMS
Feature: Management of collections in tool_mucatalog
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
      | manager1  | Manager   | 1         | manager1@example.com |
      | manager2  | Manager   | 2         | manager2@example.com |
      | student1  | Student   | 1         | student1@example.com |
    And the following "roles" exist:
      | name               | shortname |
      | Collection viewer  | sviewer   |
      | Collection manager | smanager  |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/mucatalog:view              | Allow      | sviewer  | System       |           |
      | moodle/site:configview           | Allow      | sviewer  | System       |           |
      | tool/mucatalog:view              | Allow      | smanager | System       |           |
      | tool/mucatalog:manage            | Allow      | smanager | System       |           |
      | tool/mucatalog:addcourse         | Allow      | smanager | System       |           |
      | moodle/site:configview           | Allow      | smanager | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | viewer1   | sviewer      | System       |           |
      | viewer2   | sviewer      | Category     | CAT2      |
      | manager1  | smanager     | System       |           |
      | manager2  | smanager     | Category     | CAT2      |

  Scenario: System manager may create, update and delete Universal catalogue collections
    Given I log in as "manager1"
    And I am on the "tool_mucatalog > All collections management" page

    When I press "Add collection"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Show on front page   | 0                  |
      | Visible to guests    | 0                  |
      | Visible to all users | 1                  |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Collection name         | First collection      |
      | Short description    | First description  |
    And I click on "Add collection" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Collection name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  |
      | First collection     | 0     | System              | No                 | -                   | No                | Yes                  |                     |

    When I press "Add collection"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Collection name         | Second collection     |
      | Short description    | Second description |
      | Management category  | Cat 2              |
      | Show on front page   | 1                  |
      | Front page priority  | 77                 |
      | Visible to guests    | 1                  |
      | Visible to all users | 0                  |
      | Visible to cohorts   | Cohort 1, Cohort 3 |
    And I click on "Add collection" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Collection name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  |
      | First collection     | 0     | System              | No                 | -                   | No                | Yes                  |                     |
      | Second collection    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  |

    When I click on "Actions" "link" in the "First collection" "table_row"
    And I click on "Update collection" "link" in the "First collection" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Collection name         | First collection      |
      | Short description    | First description  |
      | Show on front page   | 0                  |
      | Visible to guests    | 0                  |
      | Visible to all users | 1                  |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Collection name         | Prvni collection      |
      | Short description    | Prvni description  |
      | Show on front page   | 1                  |
      | Front page priority  | 88                 |
      | Visible to guests    | 1                  |
      | Visible to all users | 0                  |
      | Visible to cohorts   | Cohort 2, Cohort 3 |
    And I click on "Update collection" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Collection name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  |
      | Prvni collection     | 0     | System              | Yes                | 88                  | Yes               | No                   | Cohort 2, Cohort 3  |
      | Second collection    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  |

    When I click on "Actions" "link" in the "Prvni collection" "table_row"
    And I click on "Update collection" "link" in the "Prvni collection" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Collection name         | Prvni collection      |
      | Short description    | Prvni description  |
      | Show on front page   | 1                  |
      | Front page priority  | 88                 |
      | Visible to guests    | 1                  |
      | Visible to all users | 0                  |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Collection name         | First collection      |
      | Short description    | First description  |
      | Show on front page   | 0                  |
      | Visible to guests    | 0                  |
      | Visible to all users | 1                  |
    And I click on "Update collection" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Collection name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  |
      | First collection     | 0     | System              | No                 | -                   | No                | Yes                  |                     |
      | Second collection    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  |

    When I click on "Actions" "link" in the "Second collection" "table_row"
    And I click on "Delete collection" "link" in the "Second collection" "table_row"
    And I click on "Delete collection" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Collection name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  |
      | First collection     | 0     | System              | No                 | -                   | No                | Yes                  |                     |
    And I should not see "Second collection"
