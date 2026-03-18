@tool @tool_mucatalog @javascript @MuTMS
Feature: Browsing of Universal catalogue
  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT2     | CAT3     |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course 00 | C00       | CAT1     |
      | Course 01 | C01       | CAT1     |
      | Course 02 | C02       | CAT2     |
      | Course 03 | C03       | CAT3     |
      | Course 04 | C04       | CAT3     |
      | Course 05 | C05       | CAT1     |
      | Course 06 | C06       | CAT1     |
      | Course 07 | C07       | CAT1     |
      | Course 08 | C08       | CAT1     |
      | Course 09 | C09       | CAT1     |
      | Course 10 | C10       | CAT1     |
      | Course 11 | C11       | CAT1     |
      | Course 12 | C12       | CAT1     |
      | Course 13 | C13       | CAT1     |
      | Course 14 | C14       | CAT1     |
      | Course 15 | C15       | CAT1     |
      | Course 16 | C16       | CAT1     |
      | Course 17 | C17       | CAT1     |
      | Course 18 | C18       | CAT1     |
      | Course 19 | C19       | CAT1     |
      | Course 20 | C20       | CAT1     |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference | public |
      | Cohort 1   | CH1      | System       |           | 1      |
      | Cohort 2   | CH2      | System       |           | 1      |
      | Cohort 3   | CH3      | System       |           | 1      |
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | viewer1   | Viewer    | 1         | viewer1@example.com  |
      | student1  | Student   | 1         | student1@example.com |
      | student2  | Student   | 2         | student2@example.com |
      | student3  | Student   | 3         | student3@example.com |
    And the following "roles" exist:
      | name               | shortname |
      | Catalogue viewer   | sviewer   |
    And the following "permission overrides" exist:
      | capability                       | permission | role     | contextlevel | reference |
      | tool/mucatalog:view              | Allow      | sviewer  | System       |           |
      | moodle/course:view               | Allow      | sviewer  | System       |           |
      | moodle/site:configview           | Allow      | sviewer  | System       |           |
    And the following "role assigns" exist:
      | user      | role         | contextlevel | reference |
      | viewer1   | sviewer      | System       |           |

  Scenario: Nobody can see Catalog menu if Universal catalogue not used
    When I am on homepage
    Then I should not see "Catalogue"

    When I log in as "guest"
    And I am on homepage
    Then I should not see "Catalogue"

    When I log in as "student1"
    And I am on homepage
    Then I should not see "Catalogue"
    And I log out

    When I log in as "viewer1"
    And I am on homepage
    Then I should not see "Catalogue"

  Scenario: Not-logged-in and guests do not see Universal catalogue if no section and collection visible
    Given the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name      | guestvisible | uservisible | status   | frontpagepriority |
      |              |           | Section 1 | 0            | 1           | active   | 9                 |
    And the following "tool_mucatalog > collections" exist:
      | contextlevel | reference | name         | guestvisible | uservisible | frontpagepriority |
      |              |           | Collection 1 | 0            | 1           | 8                 |

    When I am on homepage
    Then I should not see "Catalogue"

    When I log in as "guest"
    And I am on homepage
    Then I should not see "Catalogue"

  Scenario: Not-logged-in and guests may view Universal catalogue frontpage
    Given the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name      | guestvisible | uservisible | status   | frontpagepriority |
      |              |           | Section 1 | 1            | 1           | draft    |                   |
      |              |           | Section 2 | 1            | 1           | archived |                   |
      | Category     | CAT3      | Section 3 | 1            | 1           | active   | 99                |
      |              |           | Section 4 | 0            | 1           | active   |                   |
    And the following "tool_mucatalog > collections" exist:
      | contextlevel | reference | name         | guestvisible | uservisible | frontpagepriority |
      |              |           | Collection 1 | 1            | 1           |                   |
      |              |           | Collection 2 | 1            | 1           | 77                |
      | Category     | CAT3      | Collection 3 | 0            | 1           | 88                |
    And I am on homepage

    When I click on "Catalogue" "link" in the ".primary-navigation" "css_element"
    Then I should see "Section 3"
    And I should see "Collection 2"
    And I should see "All items"
    And I should not see "Section 1"
    And I should not see "Section 2"
    And I should not see "Section 4"
    And I should not see "Collection 1"
    And I should not see "Collection 3"

    When I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I follow "Section 3"
    Then the following fields match these values:
      | sectionid | All items |

    When I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I follow "Collection 2"
    Then the following fields match these values:
      | sectionid | Collection 2 |

    When I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I follow "All items"
    Then the following fields match these values:
      | sectionid | All items |

    When I log in as "guest"
    And I am on homepage
    And I click on "Catalogue" "link" in the ".primary-navigation" "css_element"
    Then I should see "Section 3"
    And I should see "Collection 2"
    And I should see "All items"
    And I should not see "Section 1"
    And I should not see "Section 2"
    And I should not see "Section 4"
    And I should not see "Collection 1"
    And I should not see "Collection 3"

  Scenario: Universal catalogue frontpage is skipped if there is no frontpage section or collection
    Given the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name      | guestvisible | uservisible | status   | frontpagepriority |
      |              |           | Section 1 | 1            | 1           | draft    |                   |
      |              |           | Section 2 | 1            | 1           | archived | 99                |
      | Category     | CAT3      | Section 3 | 1            | 1           | active   |                   |
      |              |           | Section 4 | 1            | 1           | active   |                   |
    And the following "tool_mucatalog > collections" exist:
      | contextlevel | reference | name         | guestvisible | uservisible | frontpagepriority |
      |              |           | Collection 1 | 0            | 0           | 88                |
      |              |           | Collection 2 | 1            | 1           |                   |
      | Category     | CAT3      | Collection 3 | 1            | 1           |                   |
    And I am on homepage

    When I click on "Catalogue" "link" in the ".primary-navigation" "css_element"
    Then the following fields match these values:
      | sectionid | All items |

    And I log in as "guest"
    And I am on homepage
    When I click on "Catalogue" "link" in the ".primary-navigation" "css_element"
    Then the following fields match these values:
      | sectionid | All items |

    And I log in as "student1"
    And I am on homepage
    When I click on "Catalogue" "link" in the ".primary-navigation" "css_element"
    Then the following fields match these values:
      | sectionid | All items |

  Scenario: Users may select sections and collections in Universal catalogue
    Given the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name      | guestvisible | uservisible | status   |
      |              |           | Section 1 | 1            | 1           | draft    |
      |              |           | Section 2 | 1            | 1           | archived |
      | Category     | CAT3      | Section 3 | 1            | 1           | active   |
      |              |           | Section 4 | 1            | 1           | active   |
    And the following "tool_mucatalog > collections" exist:
      | contextlevel | reference | name         | guestvisible | uservisible |
      |              |           | Collection 1 | 0            | 0           |
      |              |           | Collection 2 | 1            | 1           |
      | Category     | CAT3      | Collection 3 | 1            | 1           |
    And the following "tool_mucatalog > items" exist:
      | section    | type   | reference |
      | Section 1  | course | Course 01 |
      | Section 2  | course | Course 02 |
      | Section 3  | course | Course 03 |
      | Section 4  | course | Course 04 |
      | Section 4  | course | Course 05 |
    When the following "tool_mucatalog > collection_items" exist:
      | collection   | item      |
      | Collection 1 | Course 01 |
      | Collection 2 | Course 02 |
      | Collection 3 | Course 03 |

    When I am on the "tool_mucatalog > Catalogue Frontpage" page
    Then the following fields match these values:
      | sectionid | All items |
    And I should see "Course 03"
    And I should see "Course 04"
    And I should see "Course 05"
    And I should not see "Course 01"
    And I should not see "Course 02"

    When I set the following fields to these values:
      | sectionid | Collection 2 |
    Then I should see "No items found"

    When I set the following fields to these values:
      | sectionid | Collection 3 |
    Then I should see "Course 03"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 04"
    And I should not see "Course 05"

    When I set the following fields to these values:
      | sectionid | Section 3 |
    Then I should see "Course 03"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 04"
    And I should not see "Course 05"

    When I set the following fields to these values:
      | sectionid | Section 4 |
    Then I should see "Course 04"
    And I should see "Course 05"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 03"

    And I log in as "guest"

    When I am on the "tool_mucatalog > Catalogue Frontpage" page
    Then the following fields match these values:
      | sectionid | All items |
    And I should see "Course 03"
    And I should see "Course 04"
    And I should see "Course 05"
    And I should not see "Course 01"
    And I should not see "Course 02"

    When I set the following fields to these values:
      | sectionid | Collection 2 |
    Then I should see "No items found"

    When I set the following fields to these values:
      | sectionid | Collection 3 |
    Then I should see "Course 03"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 04"
    And I should not see "Course 05"

    When I set the following fields to these values:
      | sectionid | Section 3 |
    Then I should see "Course 03"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 04"
    And I should not see "Course 05"

    When I set the following fields to these values:
      | sectionid | Section 4 |
    Then I should see "Course 04"
    And I should see "Course 05"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 03"

    And I log in as "student1"

    When I am on the "tool_mucatalog > Catalogue Frontpage" page
    Then the following fields match these values:
      | sectionid | All items |
    And I should see "Course 03"
    And I should see "Course 04"
    And I should see "Course 05"
    And I should not see "Course 01"
    And I should not see "Course 02"

    When I set the following fields to these values:
      | sectionid | Collection 2 |
    Then I should see "No items found"

    When I set the following fields to these values:
      | sectionid | Collection 3 |
    Then I should see "Course 03"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 04"
    And I should not see "Course 05"

    When I set the following fields to these values:
      | sectionid | Section 3 |
    Then I should see "Course 03"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 04"
    And I should not see "Course 05"

    When I set the following fields to these values:
      | sectionid | Section 4 |
    Then I should see "Course 04"
    And I should see "Course 05"
    And I should not see "Course 01"
    And I should not see "Course 02"
    And I should not see "Course 03"

  Scenario: Users may load more items in Universal catalogue
    Given the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name      | guestvisible | uservisible | status   |
      |              |           | Section 1 | 1            | 1           | active   |
      |              |           | Section 2 | 1            | 1           | active   |
    And the following "tool_mucatalog > items" exist:
      | section    | type   | reference |
      | Section 1  | course | Course 01 |
      | Section 1  | course | Course 02 |
      | Section 1  | course | Course 03 |
      | Section 1  | course | Course 04 |
      | Section 1  | course | Course 05 |
      | Section 1  | course | Course 06 |
      | Section 1  | course | Course 07 |
      | Section 1  | course | Course 08 |
      | Section 1  | course | Course 09 |
      | Section 1  | course | Course 10 |
      | Section 1  | course | Course 11 |
      | Section 1  | course | Course 12 |
      | Section 1  | course | Course 13 |
      | Section 1  | course | Course 14 |

    And I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I set the following fields to these values:
      | sectionid | Section 1 |
    And I should see "Course 01"
    And I should see "Course 12"
    And I should not see "Course 13"
    And I should not see "Course 14"

    When I press "Show more items"
    Then I should see "Course 01"
    And I should see "Course 12"
    And I should see "Course 13"
    And I should see "Course 14"

    And I log in as "guest"

    And I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I should see "Course 01"
    And I should see "Course 12"
    And I should not see "Course 13"
    And I should not see "Course 14"

    When I press "Show more items"
    Then I should see "Course 01"
    And I should see "Course 12"
    And I should see "Course 13"
    And I should see "Course 14"

    And I log in as "student1"

    And I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I set the following fields to these values:
      | sectionid | Section 1 |
    And I should see "Course 01"
    And I should see "Course 12"
    And I should not see "Course 13"
    And I should not see "Course 14"

    When I press "Show more items"
    Then I should see "Course 01"
    And I should see "Course 12"
    And I should see "Course 13"
    And I should see "Course 14"

  Scenario: Users may search for items by name in Universal catalogue
    Given the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name      | guestvisible | uservisible | status   |
      |              |           | Section 1 | 1            | 1           | active   |
      |              |           | Section 2 | 1            | 1           | active   |
    And the following "tool_mucatalog > items" exist:
      | section    | type   | reference |
      | Section 1  | course | Course 01 |
      | Section 1  | course | Course 02 |
      | Section 1  | course | Course 03 |
      | Section 2  | course | Course 10 |
      | Section 2  | course | Course 11 |
      | Section 2  | course | Course 12 |

    And I am on the "tool_mucatalog > Catalogue Frontpage" page

    When I set the following fields to these values:
      | search | Course 0 |
    And I press the enter key
    Then I should see "Course 01"
    And I should see "Course 02"
    And I should see "Course 03"
    And I should not see "Course 10"

    When I set the following fields to these values:
      | search | Course 1 |
    And I press the enter key
    Then I should see "Course 10"
    And I should see "Course 11"
    And I should see "Course 12"
    And I should not see "Course 01"

    When I set the following fields to these values:
      | sectionid | Section 1 |
    And I set the following fields to these values:
      | search | Course 1 |
    Then I should see "No items found"

    And I log in as "student1"

    And I am on the "tool_mucatalog > Catalogue Frontpage" page

    When I set the following fields to these values:
      | search | Course 0 |
    And I press the enter key
    Then I should see "Course 01"
    And I should see "Course 02"
    And I should see "Course 03"
    And I should not see "Course 10"

    When I set the following fields to these values:
      | search | Course 1 |
    And I press the enter key
    Then I should see "Course 10"
    And I should see "Course 11"
    And I should see "Course 12"
    And I should not see "Course 01"

    When I set the following fields to these values:
      | sectionid | Section 1 |
    And I set the following fields to these values:
      | search | Course 1 |
    Then I should see "No items found"

  Scenario: Users may filter items by type in Universal catalogue
    Given I skip tests if "tool_muprog" is not installed
    And the following "tool_muprog > programs" exist:
      | fullname   | idnumber |
      | Program 01 | PR1      |
      | Program 02 | PR2      |
    And the following "tool_mucatalog > sections" exist:
      | contextlevel | reference | name      | guestvisible | uservisible | status   |
      |              |           | Section 1 | 1            | 1           | active   |
    And the following "tool_mucatalog > items" exist:
      | section    | type    | reference  |
      | Section 1  | course  | Course 01  |
      | Section 1  | course  | Course 02  |
      | Section 1  | program | Program 01 |
      | Section 1  | program | Program 02 |

    And I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I should see "Course 01"
    And I should see "Course 02"
    And I should see "Program 01"
    And I should see "Program 02"

    When I set the following fields to these values:
      | Course | 1 |
    Then I should see "Course 01"
    And I should see "Course 02"
    And I should not see "Program 01"
    And I should not see "Program 02"

    When I set the following fields to these values:
      | Program | 1 |
    Then I should see "Program 01"
    And I should see "Program 02"
    And I should not see "Course 01"
    And I should not see "Course 02"

    When I set the following fields to these values:
      | Any type | 1 |
    Then I should see "Course 01"
    And I should see "Course 02"
    And I should see "Program 01"
    And I should see "Program 02"

    And I log in as "student1"

    And I am on the "tool_mucatalog > Catalogue Frontpage" page
    And I should see "Course 01"
    And I should see "Course 02"
    And I should see "Program 01"
    And I should see "Program 02"

    When I set the following fields to these values:
      | Course | 1 |
    Then I should see "Course 01"
    And I should see "Course 02"
    And I should not see "Program 01"
    And I should not see "Program 02"

    When I set the following fields to these values:
      | Program | 1 |
    Then I should see "Program 01"
    And I should see "Program 02"
    And I should not see "Course 01"
    And I should not see "Course 02"

    When I set the following fields to these values:
      | Any type | 1 |
    Then I should see "Course 01"
    And I should see "Course 02"
    And I should see "Program 01"
    And I should see "Program 02"
