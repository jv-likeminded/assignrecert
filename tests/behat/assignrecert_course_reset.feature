@mod @mod_assignrecert
Feature: Assign reset
  In order to reuse past Assignss
  As a teacher
  I need to remove all previous data.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Tina | Teacher1 | teacher1@example.com |
      | student1 | Sam1 | Student1 | student1@example.com |
      | student2 | Sam2 | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment Recert" to section "1" and I fill the form with:
      | Assignment Recert name | Test assignmentrecert name |
      | Description | Submit your online text |
      | assignrecertsubmission_onlinetext_enabled | 1 |
      | assignrecertsubmission_onlinetext_wordlimit_enabled | 1 |
      | assignrecertsubmission_onlinetext_wordlimit | 10 |
      | assignrecertsubmission_file_enabled | 0 |

  Scenario: Use course reset to clear all attempt data
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignmentrecert name"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student first submission |
    And I press "Save changes"
    Then I should see "Submitted"
    And I should see "I'm the student first submission"
    And I should see "Not graded"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test assignmentrecert name"
    And I navigate to "View all submissions" in current page administration
    Then "Sam1 Student1" row "Status" column of "generaltable" table should contain "Submitted"
    And I navigate to "Reset" node in "Course administration"
    And I set the following fields to these values:
        | Delete all submissions | 1  |
    And I press "Reset course"
    And I press "Continue"
    And I follow "Course 1"
    And I follow "Test assignmentrecert name"
    And I navigate to "View all submissions" in current page administration
    Then "Sam1 Student1" row "Status" column of "generaltable" table should contain "No submission"

  @javascript
  Scenario: Use course reset to remove user overrides.
    When I follow "Test assignmentrecert name"
    And I navigate to "User overrides" in current page administration
    And I press "Add user override"
    And I set the following fields to these values:
        | Override user    | Student1  |
        | id_duedate_enabled | 1 |
        | duedate[day]       | 1 |
        | duedate[month]     | January |
        | duedate[year]      | ## +2 years ## Y ## |
        | duedate[hour]      | 08 |
        | duedate[minute]    | 00 |
    And I press "Save"
    And I should see "Sam1 Student1"
    And I navigate to "Reset" node in "Course administration"
    And I set the following fields to these values:
        | Delete all user overrides | 1  |
    And I press "Reset course"
    And I press "Continue"
    And I follow "Course 1"
    And I follow "Test assignmentrecert name"
    And I navigate to "User overrides" in current page administration
    Then I should not see "Sam1 Student1"

  Scenario: Use course reset to remove group overrides.
    When I follow "Test assignmentrecert name"
    And I navigate to "Group overrides" in current page administration
    And I press "Add group override"
    And I set the following fields to these values:
        | Override group   | Group 1  |
        | id_duedate_enabled | 1 |
        | duedate[day]       | 1 |
        | duedate[month]     | January |
        | duedate[year]      | ## +2 years ## Y ## |
        | duedate[hour]      | 08 |
        | duedate[minute]    | 00 |
    And I press "Save"
    And I should see "Group 1"
    And I navigate to "Reset" node in "Course administration"
    And I set the following fields to these values:
        | Delete all group overrides | 1  |
    And I press "Reset course"
    And I press "Continue"
    And I follow "Course 1"
    And I follow "Test assignmentrecert name"
    And I navigate to "Group overrides" in current page administration
    Then I should not see "Group 1"

  Scenario: Use course reset to reset blind marking assignmentrecert.
    Given I follow "Test assignmentrecert name"
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
        | blindmarking | 1 |
    And I press "Save"
    When I follow "Test assignmentrecert name"
    And I navigate to "View all submissions" in current page administration
    And I select "Reveal learner identities" from the "Grading action" singleselect
    And I press "Continue"
    And I should see "Sam1 Student1"
    And I am on "Course 1" course homepage
    When I navigate to "Reset" node in "Course administration"
    And I set the following fields to these values:
        | Delete all submissions | 1 |
    And I press "Reset course"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I follow "Test assignmentrecert name"
    And I navigate to "View all submissions" in current page administration
    Then I should not see "Sam1 Student1"
