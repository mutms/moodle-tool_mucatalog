@tool @tool_mucatalog @javascript @MuTMS
Feature: Management of sections in tool_mucatalog
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT2     |
      | Course 2 | C2        | CAT2     |
      | Course 3 | C3        | CAT3     |
      | Course 4 | C4        | CAT3     |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference | public |
      | Cohort 1   | CH1      | System       |           | 1      |
      | Cohort 2   | CH2      | System       |           | 1      |
      | Cohort 3   | CH3      | System       |           | 1      |
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

  Scenario: System manager may create and update Universal catalogue sections
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

  Scenario: Category manager may create and update Universal catalogue sections
    Given I log in as "manager2"
    And I am on the "Cat 2" "tool_mucatalog > Sections management" page

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
      | First section     | 0     | Cat 2               | No                 | -                   | No                | Yes                  |                     | Active         |

    When I press "Add section"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section name         | Second section     |
      | Short description    | Second description |
      | Management category  | Cat 3              |
      | Show on front page   | 1                  |
      | Front page priority  | 77                 |
      | Visible to guests    | 1                  |
      | Visible to all users | 0                  |
      | Visible to cohorts   | Cohort 1, Cohort 3 |
      | Draft                | 1                  |
    And I click on "Add section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Items | Management category | Show on front page | Front page priority | Visible to guests | Visible to all users | Visible to cohorts  | Section status |
      | First section     | 0     | Cat 2               | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | Cat 3               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Draft          |

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
      | Prvni section     | 0     | Cat 2               | Yes                | 88                  | Yes               | No                   | Cohort 2, Cohort 3  | Active         |
      | Second section    | 0     | Cat 3               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Draft          |

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
      | First section     | 0     | Cat 2               | No                 | -                   | No                | Yes                  |                     | Active         |
      | Second section    | 0     | Cat 3               | Yes                | 77                  | Yes               | No                   | Cohort 1, Cohort 3  | Draft          |

  Scenario: System manager may change status of Universal catalogue sections
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | draft    | System       |           |
      | Second section | active   | Category     | CAT1      |
      | Third section  | archived | Category     | CAT2      |
      | Fourth section | draft    | Category     | CAT3      |
    And I log in as "manager1"
    And I am on the "tool_mucatalog > All sections management" page

    When I click on "Actions" "link" in the "First section" "table_row"
    And I click on "Activate section" "link" in the "First section" "table_row"
    And I click on "Activate section" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Archive section" "link" in the "Second section" "table_row"
    And I click on "Archive section" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Third section" "table_row"
    And I click on "Restore section" "link" in the "Third section" "table_row"
    And I click on "Restore section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | First section     | Active         |
      | Second section    | Archived       |
      | Third section     | Active         |
      | Fourth section    | Draft          |

    And I follow "Fourth section"
    And I should see "Draft" in the "Section status" definition list item

    When I click on "Activate section" "link"
    And I click on "Activate section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active" in the "Section status" definition list item

    When I click on "Archive section" "link"
    And I click on "Archive section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Archived" in the "Section status" definition list item

    When I click on "Restore section" "link"
    And I click on "Restore section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active" in the "Section status" definition list item

  Scenario: Category manager may change status of Universal catalogue sections
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | draft    | Category     | CAT2      |
      | Second section | active   | Category     | CAT3      |
      | Third section  | archived | Category     | CAT3      |
      | Fourth section | draft    | Category     | CAT2      |
    And I log in as "manager2"
    And I am on the "Cat 2" "tool_mucatalog > Sections management" page

    When I click on "Actions" "link" in the "First section" "table_row"
    And I click on "Activate section" "link" in the "First section" "table_row"
    And I click on "Activate section" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Second section" "table_row"
    And I click on "Archive section" "link" in the "Second section" "table_row"
    And I click on "Archive section" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Third section" "table_row"
    And I click on "Restore section" "link" in the "Third section" "table_row"
    And I click on "Restore section" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | First section     | Active         |
      | Second section    | Archived       |
      | Third section     | Active         |
      | Fourth section    | Draft          |

    And I follow "Fourth section"
    And I should see "Draft" in the "Section status" definition list item

    When I click on "Activate section" "link"
    And I click on "Activate section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active" in the "Section status" definition list item

    When I click on "Archive section" "link"
    And I click on "Archive section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Archived" in the "Section status" definition list item

    When I click on "Restore section" "link"
    And I click on "Restore section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Active" in the "Section status" definition list item

  Scenario: System manager may move Universal catalogue sections
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | active   | System       |           |
      | Second section | active   | Category     | CAT1      |
    And I log in as "manager1"
    And I am on the "tool_mucatalog > All sections management" page
    And I follow "First section"
    And I should see "System" in the "Management category" definition list item

    When I click on "Move section" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Management category  | Cat 3              |
    And I click on "Move section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Cat 3" in the "Management category" definition list item

    When I click on "Move section" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Management category  | System             |
    And I click on "Move section" "button" in the ".modal-dialog" "css_element"
    Then I should see "System" in the "Management category" definition list item

  Scenario: Category manager may move Universal catalogue sections
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | active   | Category     | CAT2      |
      | Second section | active   | Category     | CAT3      |
    And I log in as "manager2"
    And I am on the "Cat 2" "tool_mucatalog > Sections management" page
    And I follow "First section"
    And I should see "Cat 2" in the "Management category" definition list item

    When I click on "Move section" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Management category  | Cat 3              |
    And I click on "Move section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Cat 3" in the "Management category" definition list item

    When I click on "Move section" "link"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Management category  | Cat 2             |
    And I click on "Move section" "button" in the ".modal-dialog" "css_element"
    Then I should see "Cat 2" in the "Management category" definition list item

  Scenario: System manager may delete Universal catalogue sections
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | draft    | System       |           |
      | Second section | active   | Category     | CAT1      |
      | Third section  | archived | Category     | CAT2      |
      | Fourth section | draft    | System       |           |
      | Fifth section  | archived | Category     | CAT2      |
    And I log in as "manager1"
    And I am on the "tool_mucatalog > All sections management" page

    When I click on "Actions" "link" in the "First section" "table_row"
    And I click on "Delete section" "link" in the "First section" "table_row"
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Third section" "table_row"
    And I click on "Delete section" "link" in the "Third section" "table_row"
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    Then I should not see "First section"
    And I should not see "Third section"
    And the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | Second section    | Active         |
      | Fourth section    | Draft          |
      | Fifth section     | Archived       |

    And I follow "Fourth section"
    When I click on "Delete" action from "Section actions" dropdown
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Fourth section"
    And the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | Second section    | Active         |
      | Fifth section     | Archived       |

    And I follow "Fifth section"
    When I click on "Delete" action from "Section actions" dropdown
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    Then I should see "No sections found"
    And I am on the "tool_mucatalog > All sections management" page
    And I should not see "Fifth section"
    And the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | Second section    | Active         |

  Scenario: Category manager may delete Universal catalogue sections
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | draft    | Category     | CAT2      |
      | Second section | active   | Category     | CAT2      |
      | Third section  | archived | Category     | CAT3      |
      | Fourth section | draft    | Category     | CAT2      |
      | Fifth section  | archived | Category     | CAT2      |
    And I log in as "manager2"
    And I am on the "Cat 2" "tool_mucatalog > Sections management" page

    When I click on "Actions" "link" in the "First section" "table_row"
    And I click on "Delete section" "link" in the "First section" "table_row"
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Third section" "table_row"
    And I click on "Delete section" "link" in the "Third section" "table_row"
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    Then I should not see "First section"
    And I should not see "Third section"
    And the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | Second section    | Active         |
      | Fourth section    | Draft          |
      | Fifth section     | Archived       |

    And I follow "Fourth section"
    When I click on "Delete" action from "Section actions" dropdown
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Fourth section"
    And the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | Second section    | Active         |
      | Fifth section     | Archived       |

    And I follow "Fifth section"
    When I click on "Delete" action from "Section actions" dropdown
    And I click on "Delete section" "button" in the ".modal-dialog" "css_element"
    And I am on the "Cat 2" "tool_mucatalog > Sections management" page
    Then I should not see "Fifth section"
    And the following should exist in the "reportbuilder-table" table:
      | Section name      | Section status |
      | Second section    | Active         |

  Scenario: Category manager may create, update and delete course items in Universal catalogue sections
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | active   | Category     | CAT2      |
      | Second section | active   | Category     | CAT3      |
    And I log in as "manager2"
    And I am on the "First section" "tool_mucatalog > Section" page
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"

    When I press "Add items"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Course | 1 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Courses | Course 1, Course 2 |
    And I click on "Add items" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name | Item type | Hidden before  | Hidden after   | Item status |
      | Course 1  | Course    | -              | -              | Active      |
      | Course 2  | Course    | -              | -              | Active      |

    When I press "Add items"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Course | 1 |
    And I click on "Continue" "button" in the ".modal-dialog" "css_element"
    And I set the following fields to these values:
      | Courses               | Course 3 |
      | hiddenbefore[enabled] | 1        |
      | hiddenbefore[day]     | 5        |
      | hiddenbefore[month]   | 11       |
      | hiddenbefore[year]    | 2020     |
      | hiddenbefore[hour]    | 09       |
      | hiddenbefore[minute]  | 00       |
      | hiddenafter[enabled]  | 1        |
      | hiddenafter[day]      | 6        |
      | hiddenafter[month]    | 10       |
      | hiddenafter[year]     | 2035     |
      | hiddenafter[hour]     | 08       |
      | hiddenafter[minute]   | 00       |
      | Draft                 | 1        |
    And I click on "Add items" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name | Item type | Hidden before  | Hidden after   | Item status |
      | Course 1  | Course    | -              | -              | Active      |
      | Course 2  | Course    | -              | -              | Active      |
      | Course 3  | Course    | 5/11/20, 09:00 | 6/10/35, 08:00 | Draft       |

    When I click on "Actions" "link" in the "Course 1" "table_row"
    And I click on "Update item" "link" in the "Course 1" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Sync item name        | 1        |
      | hiddenbefore[enabled] | 0        |
      | hiddenafter[enabled]  | 0        |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sync item name        | 0        |
      | Item name             | Course X |
      | hiddenbefore[enabled] | 1        |
      | hiddenbefore[day]     | 5        |
      | hiddenbefore[month]   | 11       |
      | hiddenbefore[year]    | 2021     |
      | hiddenbefore[hour]    | 09       |
      | hiddenbefore[minute]  | 00       |
      | hiddenafter[enabled]  | 1        |
      | hiddenafter[day]      | 6        |
      | hiddenafter[month]    | 10       |
      | hiddenafter[year]     | 2034     |
      | hiddenafter[hour]     | 08       |
      | hiddenafter[minute]   | 00       |
    And I click on "Update item" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name | Item type | Hidden before  | Hidden after   | Item status |
      | Course X  | Course    | 5/11/21, 09:00 | 6/10/34, 08:00 | Active      |
      | Course 2  | Course    | -              | -              | Active      |
      | Course 3  | Course    | 5/11/20, 09:00 | 6/10/35, 08:00 | Draft       |

    When I click on "Actions" "link" in the "Course X" "table_row"
    And I click on "Update item" "link" in the "Course X" "table_row"
    And the following fields in the ".modal-dialog" "css_element" match these values:
      | Sync item name        | 0        |
      | Item name             | Course X |
      | hiddenbefore[enabled] | 1        |
      | hiddenafter[enabled]  | 1        |
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sync item name        | 1        |
      | hiddenbefore[enabled] | 0        |
      | hiddenafter[enabled]  | 0        |
    And I click on "Update item" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name | Item type | Hidden before  | Hidden after   | Item status |
      | Course 1  | Course    | -              | -              | Active      |
      | Course 2  | Course    | -              | -              | Active      |
      | Course 3  | Course    | 5/11/20, 09:00 | 6/10/35, 08:00 | Draft       |

    And I follow "Course 1"
    And I should not see "Course X"
    And I should see "Course 1" in the "Course" definition list item
    And I should see "Yes" in the "Sync item name" definition list item
    And I should see "Not set" in the "Hidden before" definition list item
    And I should see "Not set" in the "Hidden after" definition list item
    And I should see "Active" in the "Item status" definition list item

    When I press "Update item"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sync item name        | 0        |
      | Item name             | Course X |
      | hiddenbefore[enabled] | 1        |
      | hiddenbefore[day]     | 5        |
      | hiddenbefore[month]   | 11       |
      | hiddenbefore[year]    | 2021     |
      | hiddenbefore[hour]    | 09       |
      | hiddenbefore[minute]  | 00       |
      | hiddenafter[enabled]  | 1        |
      | hiddenafter[day]      | 6        |
      | hiddenafter[month]    | 10       |
      | hiddenafter[year]     | 2034     |
      | hiddenafter[hour]     | 08       |
      | hiddenafter[minute]   | 00       |
    And I click on "Update item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Course X"
    And I should see "Course 1" in the "Course" definition list item
    And I should see "No" in the "Sync item name" definition list item
    And I should see "Friday, 5 November 2021, 9:00" in the "Hidden before" definition list item
    And I should see "Friday, 6 October 2034, 8:00" in the "Hidden after" definition list item
    And I should see "Active" in the "Item status" definition list item

    When I press "Update item"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Sync item name        | 1        |
      | hiddenbefore[enabled] | 0        |
      | hiddenafter[enabled]  | 0        |
    And I click on "Update item" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Course X"
    And I should see "Course 1" in the "Course" definition list item
    And I should see "Yes" in the "Sync item name" definition list item
    And I should see "Not set" in the "Hidden before" definition list item
    And I should see "Not set" in the "Hidden after" definition list item
    And I should see "Active" in the "Item status" definition list item

  Scenario: Category manager may change status of course items in Universal catalogue
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | active   | Category     | CAT2      |
      | Second section | active   | Category     | CAT3      |
    And the following "tool_mucatalog > items" exist:
      | section       | type   | reference | status   |
      | First section | course | Course 1  | draft    |
      | First section | course | Course 2  | active   |
      | First section | course | Course 3  | archived |
      | First section | course | Course 4  | draft    |
    And I log in as "manager2"
    And I am on the "First section" "tool_mucatalog > Section" page
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"
    And the following should exist in the "reportbuilder-table" table:
      | Item name | Item type | Hidden before  | Hidden after   | Item status |
      | Course 1  | Course    | -              | -              | Draft       |
      | Course 2  | Course    | -              | -              | Active      |
      | Course 3  | Course    | -              | -              | Archived    |
      | Course 4  | Course    | -              | -              | Draft       |

    When I click on "Actions" "link" in the "Course 1" "table_row"
    And I click on "Activate item" "link" in the "Course 1" "table_row"
    And I click on "Activate item" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Course 2" "table_row"
    And I click on "Archive item" "link" in the "Course 2" "table_row"
    And I click on "Archive item" "button" in the ".modal-dialog" "css_element"
    And I click on "Actions" "link" in the "Course 3" "table_row"
    And I click on "Restore item" "link" in the "Course 3" "table_row"
    And I click on "Restore item" "button" in the ".modal-dialog" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name | Item type | Hidden before  | Hidden after   | Item status |
      | Course 1  | Course    | -              | -              | Active      |
      | Course 2  | Course    | -              | -              | Archived    |
      | Course 3  | Course    | -              | -              | Active      |
      | Course 4  | Course    | -              | -              | Draft       |

    And I follow "Course 4"
    And I should see "Course 4" in the "Course" definition list item
    And I should see "Draft" in the "Item status" definition list item

    When I click on "Activate item" "link"
    And I click on "Activate item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Course 4" in the "Course" definition list item
    And I should see "Active" in the "Item status" definition list item

    When I click on "Archive item" "link"
    And I click on "Archive item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Course 4" in the "Course" definition list item
    And I should see "Archived" in the "Item status" definition list item

    When I click on "Restore item" "link"
    And I click on "Restore item" "button" in the ".modal-dialog" "css_element"
    Then I should see "Course 4" in the "Course" definition list item
    And I should see "Active" in the "Item status" definition list item

  Scenario: Category manager may move course items between sections in Universal catalogue
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | active   | Category     | CAT2      |
      | Second section | active   | Category     | CAT3      |
    And the following "tool_mucatalog > items" exist:
      | section       | type   | reference |
      | First section | course | Course 1  |
      | First section | course | Course 2  |
    And I log in as "manager2"
    And I am on the "First section" "tool_mucatalog > Section" page
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"

    When I click on "Actions" "link" in the "Course 1" "table_row"
    And I click on "Move item" "link" in the "Course 1" "table_row"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section | Second section |
    And I click on "Move item" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Course 1"
    And the following should exist in the "reportbuilder-table" table:
      | Item name | Item type |
      | Course 2  | Course    |
    And I am on the "Second section" "tool_mucatalog > Section" page
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"
    And the following should exist in the "reportbuilder-table" table:
      | Item name | Item type |
      | Course 1  | Course    |
    And I follow "Course 1"
    And I should see "Second section" in the "Section" definition list item
    And I should see "Course 1" in the "Course" definition list item

    When I click on "Move item" action from "Item actions" dropdown
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Section | First section |
    And I click on "Move item" "button" in the ".modal-dialog" "css_element"
    Then I should see "First section" in the "Section" definition list item
    And I should see "Course 1" in the "Course" definition list item

  Scenario: Category manager may delete course items in Universal catalogue
    Given the following "tool_mucatalog > sections" exist:
      | name           | status   | contextlevel | reference |
      | First section  | active   | Category     | CAT2      |
      | Second section | active   | Category     | CAT3      |
    And the following "tool_mucatalog > items" exist:
      | section       | type   | reference | status   |
      | First section | course | Course 1  | draft    |
      | First section | course | Course 2  | active   |
      | First section | course | Course 3  | archived |
      | First section | course | Course 4  | draft    |
    And I log in as "manager2"
    And I am on the "First section" "tool_mucatalog > Section" page
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"

    When I click on "Actions" "link" in the "Course 1" "table_row"
    And I click on "Delete item" "link" in the "Course 1" "table_row"
    And I click on "Delete item" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Course 1"

    When I click on "Actions" "link" in the "Course 3" "table_row"
    And I click on "Delete item" "link" in the "Course 3" "table_row"
    And I click on "Delete item" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Course 3"
    And the following should exist in the "reportbuilder-table" table:
      | Item name | Item type |
      | Course 2  | Course    |
      | Course 4  | Course    |
    And I follow "Course 4"

    When I click on "Delete item" action from "Item actions" dropdown
    And I click on "Delete item" "button" in the ".modal-dialog" "css_element"
    Then I should not see "Course 4"
    And the following should exist in the "reportbuilder-table" table:
      | Item name | Item type |
      | Course 2  | Course    |
