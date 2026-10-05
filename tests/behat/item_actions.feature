@tool @tool_mucatalog @javascript @MuTMS
Feature: Registration actions on Universal catalogue item page
  Background:
    Given the following "courses" exist:
      | fullname  | shortname |
      | Course 01 | C01       |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference |
      | Cohort 1   | CH1      | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname  | email                |
      | student1  | Student   | 1         | student1@example.com |
      | student2  | Student   | 2         | student2@example.com |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
    And the following "tool_mucatalog > sections" exist:
      | name           | status | guestvisible | uservisible | cohortvisible |
      | Public section | active | 0            | 1           |               |
      | Cohort section | active | 0            | 0           | CH1           |
    And the following "tool_mucatalog > items" exist:
      | section        | type   | reference |
      | Public section | course | Course 01 |

  Scenario: Cohort member may self-allocate to program from Universal catalogue item page
    Given I skip tests if "tool_muprog" is not installed
    And the following "tool_muprog > programs" exist:
      | fullname   | idnumber | sources        |
      | Program 01 | PR1      | selfallocation |
    And the following "tool_mucatalog > items" exist:
      | section        | type    | reference  |
      | Cohort section | program | Program 01 |

    When I log in as "student2"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Course 01"
    And I should not see "Program 01"
    And I log out

    When I log in as "student1"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    And I should see "Course 01"
    And I follow "Program 01"
    Then I should see "Program 01" in the "#tool_mucatalog-item" "css_element"
    And I should not see "Enrolled"
    And "Open" "link" should not exist in the ".item-detail-actions" "css_element"

    When I click on "Sign up" "button" in the ".item-detail-actions" "css_element"
    And I click on "Sign up" "button" in the "dialog[open]" "css_element"
    Then I should see "Open" in the "Program status" definition list item

    When I am on the "tool_mucatalog > Catalogue All Items" page
    And I follow "Program 01"
    Then I should see "Enrolled" in the "#tool_mucatalog-item" "css_element"
    And "Sign up" "button" should not exist
    And "Open" "link" should exist in the ".item-detail-actions" "css_element"

  Scenario: Cohort member may self-assign to certification from Universal catalogue item page
    Given I skip tests if "tool_mucertify" is not installed
    And the following "tool_muprog > programs" exist:
      | fullname   | idnumber | sources   |
      | Program 01 | PR1      | mucertify |
    And the following "tool_mucertify > certifications" exist:
      | fullname         | idnumber | program1 | sources        |
      | Certification 01 | CT1      | PR1      | selfassignment |
    And the following "tool_mucatalog > items" exist:
      | section        | type          | reference        |
      | Cohort section | certification | Certification 01 |

    When I log in as "student2"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Course 01"
    And I should not see "Certification 01"
    And I log out

    When I log in as "student1"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    And I should see "Course 01"
    And I follow "Certification 01"
    Then I should see "Certification 01" in the "#tool_mucatalog-item" "css_element"
    And I should not see "Enrolled"

    When I click on "Sign up" "button" in the ".item-detail-actions" "css_element"
    And I click on "Sign up" "button" in the "dialog[open]" "css_element"
    Then I should see "Not certified" in the "Certification status" definition list item

    When I am on the "tool_mucatalog > Catalogue All Items" page
    And I follow "Certification 01"
    Then I should see "Enrolled" in the "#tool_mucatalog-item" "css_element"
    And "Sign up" "button" should not exist
    And "Open" "link" should exist in the ".item-detail-actions" "css_element"
