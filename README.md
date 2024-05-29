# Moodle Plugin: Hide Correctly Answered Questions

## Description
This Moodle plugin allows teachers to hide questions that have been correctly answered in previous attempts. This can be useful in preventing students from seeing the correct answers and encourages them to attempt the questions again.

**Enhanced Feature**: In addition to allowing teachers to hide previously answered questions, this Moodle plugin now enables automatic grading of correct answers in subsequent attempts. This ensures students only focus on revisiting and reattempting questions they haven't answered correctly before. This feature encourages more effective learning and review.

### Version

Plugin version: 2.0

Released on: 14 JUNE 2023

Upgraded on: 16 SEP 2023

Authors: https://lmsace.com, LMSACE Dev Team

### Git Repository

Private Git Repository

Git URL: https://github.com/lmsace/moodle-hide-correct-questions

### Installation steps using ZIP file.

1. Download the '**hidecorrect**' from [GitHub releases](https://github.com/lmsace/moodle-hide-correct-questions/releases).
2. Log in to Moodle as a Site Administrator.
3. Go to '*Site Administration*' -> '*Plugins*' -> '*Upload Plugin*', On here upload the plugin zip '**hidecorrect.zip**'.
4. Go to ‘Site administration’ -> ‘Notifications’ , here on ‘Plugins check’ page you will see the '*Hide Correct Questions on New Attempt*' '*Quiz / Access rules*' plugin in listing.
5. Click the “Upgrade Moodle database now” button displayed on bottom of the page.
> You will get success message once the plugin installed successfully.
6. By clicking “Continue” button on success page. you will redirect to the admin notification page.

### Installation steps using Git.

1. Clone hidecorrect plugin Git repository into the folder '*mod / quiz / accessrule*'.
2. Rename the folder name into '**hidecorrect**'.
3. Go to ‘Site administration’ -> ‘Notifications’ , here on ‘Plugins check’ page you will see the '*Hide Correct Questions on New Attempt*' '*Quiz / Access rules*' plugin in listing.
4. Click the “Upgrade Moodle database now” button displayed on bottom of the page.
> You will get success message once the plugin installed successfully.
5. By clicking “Continue” button on success page. You will redirect to the admin notification page.

## Configuration
To configure the plugin settings, follow these steps:

1. Log in to your Moodle site as a '*course administrator*' or '*teacher*'.
2. Navigate to the course where the quiz is located.
3. Turn editing on.
4. Click on the quiz activity to access its settings.
5. In the quiz settings page, locate the '**Question behavior**' section and '*click on*' it.
6. Look for the '**Each attempt builds on the last**' setting and '**enable**' it. This setting allows each attempt to build on the previous one, retaining the user's previous responses and providing a cumulative learning experience.

![question_behaviour](https://github.com/lmsace/moodle-hide-correct-questions/assets/98076459/16fa0ea9-f751-4141-a221-7fa73679563b)

7. Next, scroll down to the '**Extra restrictions on attempts**' section.
8. From the dropdown setting '**Hide questions on attempt**', select the option that says '**Hide the correct answered question in new attempt**'.
9. To keep the question mark and feedback from your previous attempt on a hidden question, select the "**Auto grade the correct questions**" option in the "**Questions auto-grade**" dropdown setting.

![hidecorrect-config](https://github.com/lmsace/moodle-hide-correct-questions/assets/57126778/4bb293e0-d5bc-4191-879c-797b214d7879)

10. From the dropdown setting "**Hide questions on attempt**", select the option "**Hide the correct and partially correct answered questions on reattempt**". This will hide questions with partially correct marks or higher automatically in the next attempt.

![hide-partiall-correct](https://github.com/lmsace/moodle-hide-correct-questions/assets/98076459/77962a5e-346f-4b8f-901e-38d2fd370caf)

11. Save the quiz settings to apply the changes.
With these settings enabled, when a learner attempts the quiz again, the questions will be hidden, allowing them to reflect on their previous attempt and make improvements without directly seeing the correct answers.

>Note: It's important to communicate these settings to your learners so they understand the behavior of the quiz and the purpose behind hiding the questions on subsequent attempts.

## Contributing
Contributions to this Moodle plugin are welcome. If you encounter any issues or have suggestions for improvement, please submit them through the [GitHub repository](https://github.com/lmsace/moodle-hide-correct-questions/issues).

## Support
For support or assistance, please contact the plugin author or submit an issue through the [GitHub repository](https://github.com/lmsace/moodle-hide-correct-questions/issues).

## About
This plugin was developed by [LMSACE](https://lmsace.com/). For more information, please visit our website or contact us directly.

## Contributions

LMSACE would like to thank these main contributors for their contirbutions

1. Yuka (Shori) Kataoka, External Certified Japanese Language Instructor : Funding

2. Maemoon Naseer, Founder and CEO of Aeorax : Funding
