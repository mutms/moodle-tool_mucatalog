@tool @tool_mucatalog @javascript @MuTMS
Feature: Management of sections in tool_mucatalog
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
      | name            | shortname |
      | Section viewer  | sviewer   |
      | Section manager | smanager  |
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

  Scenario: System manager may create, update and delete Universal catalogue sections
    Given I log in as "manager1"
    And I am on the "tool_mucatalog > All sections management" page

    When I press "Add section"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Show on front page   | 0                  |
      | Visible to guests    | 0                  |
      | Visible to all users | 1                  |
      | Active               | 1                  |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section name         | First section      |
      | Short description    | First description  |
    And I click on "Add section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |

    When I press "Add section"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section name         | Second section     |
      | Short description    | Second description |
      | Management category  | Cat 2              |
      | Show on front page   | 1                  |
      | Front page priority  | 77                 |
      | Visible to guests    | 1                  |
      | Visible to all users | 0                  |
      | Visible to cohorts   | Cohort 1, Cohort 3 |
      | Draft                | 1                  |
    And I click on "Add section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Draft          |

    When I click on "Actions" "link" in the "First section" "table_row"
    And I click on "Update section" "link" in the "First section" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Section name         | First section      |
      | Short description    | First description  |
      | Show on front page   | 0                  |
      | Visible to guests    | 0                  |
      | Visible to all users | 1                  |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section name         | Prvni section      |
      | Short description    | Prvni description  |
      | Show on front page   | 1                  |
      | Front page priority  | 88                 |
      | Visible to guests    | 1                  |
      | Visible to all users | 0                  |
      | Visible to cohorts   | Cohort 2, Cohort 3 |
    And I click on "Update section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | Prvni section     | 0     | System              | Yes                | 88                  | Yes               | No                   | Cohort 2, Cohort 3  | Active         |
      | Second section    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Draft          |

    When I click on "Actions" "link" in the "Prvni section" "table_row"
    And I click on "Update section" "link" in the "Prvni section" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Section name         | Prvni section      |
      | Short description    | Prvni description  |
      | Show on front page   | 1                  |
      | Front page priority  | 88                 |
      | Visible to guests    | 1                  |
      | Visible to all users | 0                  |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section name         | First section      |
      | Short description    | First description  |
      | Show on front page   | 0                  |
      | Visible to guests    | 0                  |
      | Visible to all users | 1                  |
    And I click on "Update section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Draft          |

    When I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Activate section" "link" in the "Second section" "table_row"
    And I click on "Activate section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Active         |

    When I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Archive section" "link" in the "Second section" "table_row"
    And I click on "Archive section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Archived       |

    When I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Restore section" "link" in the "Second section" "table_row"
    And I click on "Restore section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | Cat 2               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Active         |

    And I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Archive section" "link" in the "Second section" "table_row"
    And I click on "Archive section" "button" in the ".modal-dialog" "css_element"

    When I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Delete section" "link" in the "Second section" "table_row"
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
    And I should not see "Second section"

    And I press "Add section"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section name         | Second section     |
      | Short description    | Second description |
      | Draft                | 1                  |
    And I click on "Add section" "button" in the ".modal-dialog" "css_element"
    And the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | System              | No                 | -                   | No                | Yes                  |                     | Draft          |

    When I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Delete section" "link" in the "Second section" "table_row"
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | System              | No                 | -                   | No                | Yes                  |                     | Active         |
    And I should not see "Second section"
