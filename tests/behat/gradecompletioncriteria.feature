@mod @mod_assignrecert
Feature: Set the assignmentrecert activity grade completion criteria
  In order to set the grade completion criteria
  As an admin
  I need to enable a grade type or the default feedback type

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment Recert" to section "1" and I fill the form with:
      | Assignment Recert name      | Test assignmentrecert name        |
      | Description          | Test assignmentrecert description |
      | Online text          | 1                           |
      | Use marking workflow | Yes                         |
    And I follow "Test assignmentrecert name"
    And I navigate to "Edit settings" node in "Assignment Recert administration"
    And I expand all fieldsets

  Scenario: Assignment Recert grade completion criteria cannot be set when grade type and default feedback type are both disabled
    When I set the following fields to these values:
      | grade[modgrade_type]            | None                                              |
      | assignrecertfeedback_comments_enabled | 0                                                 |
      | assignrecertfeedback_offline_enabled  | 1                                                 |
      | assignrecertfeedback_file_enabled     | 1                                                 |
      | Completion tracking             | Show activity as complete when conditions are met |
      | completionusegrade              | 1                                                 |
    And I press "Save and return to course"
    Then I should see "To enable this setting, you must select a Grade Type or enable the default Feedback Type"
    And I should not see "Add an activity or resource"

  Scenario: Assignment Recert grade completion criteria can be set when a default feedback type is enabled
    When I set the following fields to these values:
      | grade[modgrade_type]            | None                                              |
      | assignrecertfeedback_comments_enabled | 1                                                 |
      | assignrecertfeedback_offline_enabled  | 0                                                 |
      | assignrecertfeedback_file_enabled     | 0                                                 |
      | Completion tracking             | Show activity as complete when conditions are met |
      | completionusegrade              | 1                                                 |
    And I press "Save and return to course"
    Then I should see "Add an activity or resource"
    And I should not see "To enable this setting, you must select a Grade Type or enable the default Feedback Type"

  Scenario: Assignment Recert grade completion criteria can be set when a grade type is enabled
    When I set the following fields to these values:
      | grade[modgrade_type]            | Point                                              |
      | assignrecertfeedback_comments_enabled | 0                                                 |
      | assignrecertfeedback_offline_enabled  | 0                                                 |
      | assignrecertfeedback_file_enabled     | 0                                                 |
      | Completion tracking             | Show activity as complete when conditions are met |
      | completionusegrade              | 1                                                 |
    And I press "Save and return to course"
    Then I should see "Add an activity or resource"
    And I should not see "To enable this setting, you must select a Grade Type or enable the default Feedback Type"
