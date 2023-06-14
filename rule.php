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
 * Quiz access rule Implementation - quizaccess_hidecorrect.
 *
 * @package    quizaccess_hidecorrect
 * @copyright  2023 LMSACE Dev Team <lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

/**
 * Quiz access rule, helps to hide the correctly answered questions in previous attempt for new attempts.
 */
class quizaccess_hidecorrect extends quiz_access_rule_base {

    /**
     * Enable the hide correct questions in new attempt.
     * @var int
     */
    public const ENABLE = 1;

    /**
     * Disable the hide correct questions in new attempt. No need to check.
     * @var int
     */
    public const DISABLE = 0;

    /**
     * Verfiy the quiz is configured to hide the correct questions for new attempts,
     * Then create the accessrule instance for this class.
     *
     * @param quiz $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     * @return mixed
     */
    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {

        // This access rule only works, if the Each attempt builds on the last is configured yes.
        if (!isset($quizobj->get_quiz()->attemptonlast) || !$quizobj->get_quiz()->attemptonlast) {
            return null;
        }

        if (isset($quizobj->get_quiz()->hidecorrect) && $quizobj->get_quiz()->hidecorrect == self::ENABLE) {
            return new self($quizobj, $timenow);
        }
        return null;
    }

    /**
     * Initiate the setup the attempt page of quiz to hide the correct questions.
     *
     * @param moodle_page $page
     * @return void
     */
    public function setup_attempt_page($page) {
        if ($page->pagetype != 'mod-quiz-attempt') {
            return false;
        }

        $this->get_correct_questions_fromprevious($page);
    }

    /**
     * Find the last completed user attempt for this quiz.
     *
     * @return stdclass|bool Return the user last attempt object, otherwise false.
     */
    public function user_last_finished_attempt() {
        global $USER;
        // Get this user's attempts.
        $attempts = quiz_get_user_attempts($this->quiz->id, $USER->id, 'finished', true);
        if (empty($attempts)) {
            return false;
        }
        $attempt = end($attempts);

        return $attempt;
    }

    /**
     * Find the list of correclty answered questions from previous answers and generate the
     * dynamic css rules and append them into body.
     *
     * @param moodle_page $PAGE page values.
     * @return void
     */
    public function get_correct_questions_fromprevious($PAGE) {

        $lastattempt = $this->user_last_finished_attempt();
        if (!$lastattempt) {
            return false;
        }

        $attemptid = required_param('attempt', PARAM_INT);
        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $this->quizobj->get_cmid());

        $quba = question_engine::load_questions_usage_by_activity($lastattempt->uniqueid);
        // Get list of slots avialble in quiz.
        // Get the list of questions needed by this page.
        $page = optional_param('page', 0, PARAM_INT);
        $slots = $attemptobj->get_slots($page);
        if (empty($slots)) {
            $slots = $quba->get_slots();
        }
        $completed = $completedquestions = [];
        if (!empty($slots)) {
            foreach ($slots as $slot) {
                // Get the slot question previous attemp state as string in correctness. it return correct for completed questions.
                $state = $quba->get_question_state_string($slot, true);
                if ($state == 'Correct') {
                    $completed[] = $slot;
                    $completedquestions[] = $quba->get_question($slot)->id;
                }
            }
        }

        $completedquestions = [];
        foreach ($attemptobj->get_slots() as $slot) {
            $state = $quba->get_question_state_string($slot, true);
            if ($state == 'Correct') {
                $completedquestions[] = $slot;
            }
        }

        // Redirect to next page, when the questions in the current page is answered and this page is not last page.
        if ((count($slots) == count($completed)) && !$attemptobj->is_last_page($page)) {
            $nextpage = new \moodle_url('/mod/quiz/attempt.php', [
                'attempt' => $attemptid, 'cmid' => $this->quizobj->get_cmid(), 'page' => $page + 1
            ]);
            redirect($nextpage);
        }

        $uniqueid = $attemptobj->get_attempt()->uniqueid;
        $PAGE->requires->js_call_amd('quizaccess_hidecorrect/hidecorrect', 'init',  [$completed, $uniqueid, $completedquestions]);

        $this->generate_dynamic_css($completed, $uniqueid);
    }

    /**
     * Generate the dynamic css for correctly anserwed questions to hide.
     *
     * @param array $completed
     * @param string $uniqueid
     * @return void
     */
    public function generate_dynamic_css($completed, $uniqueid) {
        global $CFG;

        if (empty($completed)) {
            return false;
        }

        $rule = '';
        foreach ($completed as $questionid) {
            $selector = '#responseform #question-' . $uniqueid . '-' . $questionid;
            $rule .= $selector.' { display: none; }';
        }

        $CFG->additionalhtmltopofbody .= html_writer::tag('style', $rule);
    }

    /**
     * Add any fields that this rule requires to the quiz settings form. This
     * method is called from mod_quiz_mod_form::definition(), while the
     * security seciton is being built.
     *
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        // By default do nothing.
        $options = [
            self::DISABLE => get_string('disable'),
            self::ENABLE => get_string('hidecorrectenable', 'quizaccess_hidecorrect'),
        ];
        $mform->addElement('select', 'hidecorrect', get_string('hidecorrect', 'quizaccess_hidecorrect'), $options);
        $mform->addHelpButton('hidecorrect', 'hidecorrect', 'quizaccess_hidecorrect');
    }

    /**
     * Save the hide correct option in DB when the quiz settings form is submitted.
     *
     * @param object $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     */
    public static function save_settings($quiz) {
        global $DB;

        $data = (object) ['hidecorrect' => $quiz->hidecorrect];

        if ($record = $DB->get_record('quizaccess_hidecorrect', ['quizid' => $quiz->id])) {
            $data->id = $record->id;
            $DB->update_record('quizaccess_hidecorrect', $data);
        } else {
            $data->quizid = $quiz->id;
            $DB->insert_record('quizaccess_hidecorrect', $data);
        }
    }

    /**
     * Delete the record from hidecorrect db when the quiz is deleted.
     *
     * @param object $quiz the data from the database, including $quiz->id
     *      which is the id of the quiz being deleted.
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_hidecorrect', ['quizid' => $quiz->id]);
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query. The easiest way to understand what you need to do
     * here is probalby to read the code of quiz_access_manager::load_settings().
     *
     * If you have some settings that cannot be loaded in this way, then you can
     * use the get_extra_settings() method instead, but that has
     * performance implications.
     *
     * @param int $quizid the id of the quiz we are loading settings for. This
     *     can also be accessed as quiz.id in the SQL. (quiz is a table alisas for {quiz}.)
     * @return array with three elements:
     *     1. fields: any fields to add to the select list. These should be alised
     *        if neccessary so that the field name starts the name of the plugin.
     *     2. joins: any joins (should probably be LEFT JOINS) with other tables that
     *        are needed.
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        used named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
     */
    public static function get_settings_sql($quizid) {
        return array(
            'hidecorrect', // Select field.
            'LEFT JOIN {quizaccess_hidecorrect} hidecorrect ON hidecorrect.quizid = quiz.id', // Fetch join queyy.
            [] // Paramenters.
        );
    }
}
