@tool @tool_mucatalog @tool_mutenancy @MuTMS @javascript
Feature: Multi-tenancy use cases for Universal catalogue

  Background:
    Given I skip tests if "tool_mutenancy" is not installed
    And unnecessary Admin bookmarks block gets deleted
    And the following "tool_mutenancy > tenants" exist:
      | name     | idnumber | sitefullname     | siteshortname | archived | categoryidnumber | categoryname |
      | Tenant 1 | TEN1     | Tent Site full 1 | TSS1          | 0        | TC1              | Tenant cat 1 |
      | Tenant 2 | TEN2     | Tent Site full 2 | TSS2          | 0        | TC2              | Tenant cat 2 |
    And the following "users" exist:
      | username | firstname | lastname | email                | tenant |
      | manager1 | Tenant 1  | Manager  | manager1@example.com | TEN1   |
      | manager2 | Tenant 2  | Manager  | manager2@example.com | TEN2   |
      | user0    | User      | Zero     | user0@example.com    |        |
      | user1    | User      | One      | user1@example.com    | TEN1   |
      | user2    | User      | Two      | user2@example.com    | TEN2   |
    And the following "tool_mutenancy > tenant managers" exist:
      | tenant | user     |
      | TEN1   | manager1 |
      | TEN2   | manager2 |
    And the following "courses" exist:
      | fullname        | shortname | category |
      | Global course   | GC        | 0        |
      | Internal course | IC        | 0        |
      | Tenant course 1 | TC1C1     | TC1      |
      | Tenant course 2 | TC2C1     | TC2      |
      | Tenant course 3 | TC1C2     | TC1      |
    And the following "tool_mucatalog > sections" exist:
      | name             | status | guestvisible | uservisible | hiddenfromtenants | contextlevel | reference |
      | Global section   | active | 0            | 1           | 0                 |              |           |
      | Internal section | active | 0            | 1           | 1                 |              |           |
      | Tenant section 1 | active | 0            | 1           | 0                 | Category     | TC1       |
      | Tenant section 2 | active | 0            | 1           | 0                 | Category     | TC2       |
    And the following "tool_mucatalog > items" exist:
      | section          | type   | reference       |
      | Global section   | course | Global course   |
      | Internal section | course | Internal course |
      | Tenant section 1 | course | Tenant course 1 |
      | Tenant section 2 | course | Tenant course 2 |
    And the following "tool_mucatalog > collections" exist:
      | name                | guestvisible | uservisible | hiddenfromtenants | contextlevel | reference |
      | Global collection   | 0            | 1           | 0                 |              |           |
      | Internal collection | 0            | 1           | 1                 |              |           |
      | Tenant collection 1 | 0            | 1           | 0                 | Category     | TC1       |
    And the following "tool_mucatalog > collection_items" exist:
      | collection          | item            |
      | Global collection   | Global course   |
      | Global collection   | Tenant course 1 |
      | Global collection   | Tenant course 2 |
      | Internal collection | Internal course |
      | Tenant collection 1 | Global course   |
      | Tenant collection 1 | Tenant course 1 |

  Scenario: Users without tenant browse catalogue sections that do not belong to tenants
    Given I log in as "user0"

    When I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Global course"
    And I should see "Internal course"
    And I should not see "Tenant course 1"
    And I should not see "Tenant course 2"

  Scenario: Tenant members browse catalogue sections of own tenant and shared sections
    Given I log in as "user1"

    When I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Global course"
    And I should see "Tenant course 1"
    And I should not see "Internal course"
    And I should not see "Tenant course 2"

    When I follow "Tenant course 1"
    Then I should see "Tenant course 1"
    And I should see "Course"

    When I log out
    And I log in as "user2"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Global course"
    And I should see "Tenant course 2"
    And I should not see "Internal course"
    And I should not see "Tenant course 1"

  Scenario: Tenant manager may manage Universal catalogue of own tenant
    Given I log in as "manager1"

    When I click on "Tenant management" "link" in the ".primary-navigation" "css_element"
    And I click on "Catalogue management" "link" in the ".primary-navigation" "css_element"
    Then I should see "Section management"
    And I should see "Tenant section 1"
    And I should not see "Tenant section 2"
    And I should not see "Global section"
    And I should not see "Internal section"

    When I press "Add section"
    And I should not see "Hidden from tenants" in the "dialog[open]" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Section name      | New tenant section |
      | Short description | Just for tenant 1  |
    And I click on "Add section" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Section name       | Items | Management category | Visible to all users | Section status |
      | New tenant section | 0     | Tenant cat 1        | Yes                  | Active         |
      | Tenant section 1   | 1     | Tenant cat 1        | Yes                  | Active         |

    When I follow "New tenant section"
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"
    And I press "Add items"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | type | course |
    And I click on "Continue" "button" in the "dialog[open]" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Courses | Tenant course 3 |
    And I click on "Add items" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name       | Item type | Item status |
      | Tenant course 3 | Course    | Active      |

    When I am on the "TC1" "tool_mucatalog > Collections management" page
    And I should see "Tenant collection 1"
    And I should not see "Global collection"
    And I press "Add collection"
    Then I should not see "Hidden from tenants" in the "dialog[open]" "css_element"
    And I click on "Cancel" "button" in the "dialog[open]" "css_element"

    When I log out
    And I log in as "user1"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Tenant course 3"
    And I should see "Tenant course 1"

    When I log out
    And I log in as "user2"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should not see "Tenant course 3"
    And I should see "Tenant course 2"
