<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Quiz access hide correct questions - String language defined.
 *
 * @package    quizaccess_hidecorrect
 * @copyright  2023 LMSACE Dev Team <lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['autograde'] = 'Questions auto-grade ';
$string['autograde_help'] = 'Auto-grading applies the grades from the last attempt to correctly answered questions in the new attempt.';
$string['autogradeenable'] = 'Auto-grade the correct questions';
$string['hidecorrect'] = 'Hide certain questions on attempt';
$string['hidecorrect_help'] = 'When enabled, hide questions that were previously answered correctly in new attempts.
<b> NOTE: This only works when the setting \'Each attempt builds on the last\' under \'Question behaviour\' is enabled for this quiz </b>';
$string['hidecorrectenable'] = 'Hide correctly answered questions in a new attempt';
$string['hidepartiallycorrect'] = 'Hide questions that have been correctly answered <b>AND</b> partially correctly answered in new attempts';
$string['pagequestioncompletes'] = '<b>All questions in this quiz have been answered correctly in a previous attempt. There is no need to attempt the quiz again.</b>';
$string['pluginname'] = 'Hide Correct Questions on New Attempt';
$string['preventreattempt'] = 'Prevent re-attempting the quiz';
$string['preventreattempt_help'] = 'When enabled, prevents the user from re-attempting the quiz after all questions have been answered correctly in a previous attempt. This is useful to ensure that users do not retake the quiz unnecessarily.';
$string['preventreattemptenable'] = 'Prevent re-attempting the quiz after all questions have been answered correctly';
$string['privacy:metadata'] = 'The Hide Correct Questions on New Attempt quiz access rule plugin does not store any personal data.';
$string['questioncompletes'] = 'All questions have been answered correctly. You cannot attempt the quiz again.';
