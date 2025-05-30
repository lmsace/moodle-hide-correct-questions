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
 * * Included the autograde unchanged questions correctly answered in previous attempt.
 *
 * @package    quizaccess_hidecorrect
 * @copyright  2023 LMSACE Dev Team <lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

/**
 * Quiz access rule, helps to hide the correctly answered questions in previous attempt for new attempts.
 */
class quizaccess_hidecorrect extends access_rule_base {

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
     * Hide the questions which is answered partialy correct.
     * @var int
     */
    public const PARTIAL = 2;

    /**
     * Verfiy the quiz is configured to hide the correct questions for new attempts,
     * Then create the accessrule instance for this class.
     *
     * @param quiz_settings $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     * @return mixed
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        global $SESSION;

        // When the user try to go back to previous page, then store the attempt id and this page number.
        if (optional_param('previous', false, PARAM_BOOL)) {
            $attemptid = optional_param('attempt', 0, PARAM_INT);
            $thispage = optional_param('thispage', 0, PARAM_INT);
            $SESSION->quizaccess_hidecorrect_previous[] = ['attemptid' => $attemptid, 'thispage' => $thispage];
        }

        // This access rule only works, if the Each attempt builds on the last is configured yes.
        if (!isset($quizobj->get_quiz()->attemptonlast) || !$quizobj->get_quiz()->attemptonlast) {
            return null;
        }

        if (isset($quizobj->get_quiz()->hidecorrect) && $quizobj->get_quiz()->hidecorrect == self::ENABLE
            || $quizobj->get_quiz()->hidecorrect == self::PARTIAL) {
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
        $attempts = quiz_get_user_attempts($this->quiz->id, $USER->id, 'finished', false);
        if (empty($attempts)) {
            return false;
        }
        $attempt = end($attempts);

        return $attempt;
    }

    /**
     * Clean up the session for the given attempt id.
     *
     * @param int|null $attemptid The attempt ID to clean up from session.
     * @return void
     */
    public function clean_up_session($attemptid = null) {
        global $SESSION;

        if (isset($SESSION->quizaccess_hidecorrect_previous) && $attemptid) {
            $list = $SESSION->quizaccess_hidecorrect_previous;
            $index = array_search($attemptid, array_column($list, 'attemptid'));
            if ($index !== false) {
                unset($list[$index]);
                $SESSION->quizaccess_hidecorrect_previous = array_values($list);
            }
        }

    }

    /**
     * Check if the session has a previous page stored for the given attempt.
     *
     * @param int $attemptid The attempt ID to check.
     * @return bool True if session has previous page info for this attempt, false otherwise.
     */
    public function has_previous_in_session($attemptid) {
        global $SESSION;

        if (empty($SESSION->quizaccess_hidecorrect_previous)) {
            return false;
        }

        $list = $SESSION->quizaccess_hidecorrect_previous;

        // If stored as an array of arrays.
        if (!empty($list)) {
            foreach ($list as $entry) {
                if (isset($entry['attemptid']) && $entry['attemptid'] == $attemptid) {
                    return $entry['thispage'] ?? false;
                }
            }
        }

        return false;
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

            $this->clean_up_session(optional_param('attempt', 0, PARAM_INT));
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

        // Verify the slots are completed.
        list($hasincompletequestions, $completed) = $this->is_page_has_incomplete_questions($page, $attemptobj, $quba);

        // Hide partially correct questions.
        $hidecorrect = $this->quiz->{"hidecorrect"} ?? self::ENABLE;

        $completedquestions = []; $pendingquestions = [];
        foreach ($attemptobj->get_slots() as $slot) {
            $state = $quba->get_question_state($slot, true);
            if ($hidecorrect == self::PARTIAL) {
                if ($state == question_state::$gradedright || $state == question_state::$gradedpartial
                    || $state == question_state::$mangrright || $state == question_state::$mangrpartial) {
                    $completedquestions[] = $slot;
                } else {
                    $pendingquestions[] = $slot;
                }
            } else {
                if ($state == question_state::$mangrright || $state == question_state::$gradedright) {
                    $completedquestions[] = $slot;
                } else {
                    $pendingquestions[] = $slot;
                }
            }
        }

        // Redirect to next page, when the questions in the current page is answered and this page is not last page.
        if (!$hasincompletequestions) {

            $attemptobj->set_currentpage($page);

            // Find the next page that contains any incomplete questions.
            $totalpages = $attemptobj->get_num_pages();
            $nextpage = null;

            // Check the user tried to go back to previous page.
            if ($thispage = $this->has_previous_in_session($attemptid)) {

                for ($p = $page - 1; $p >= 0; $p--) {
                    $hasincomplete = $this->is_page_has_incomplete_questions($p, $attemptobj, $quba)[0];
                    if ($hasincomplete) {
                        $nextpage = new \moodle_url('/mod/quiz/attempt.php', [
                            'attempt' => $attemptid,
                            'cmid' => $this->quizobj->get_cmid(),
                            'page' => $p,
                        ]);
                        break;
                    }

                    $attemptobj->set_currentpage($p);
                }

            } else {

                // Find the next page with incomplete questions.
                for ($p = $page + 1; $p < $totalpages; $p++) {
                    $hasincomplete = $this->is_page_has_incomplete_questions($p, $attemptobj, $quba)[0];
                    if ($hasincomplete) {
                        $nextpage = new \moodle_url('/mod/quiz/attempt.php', [
                            'attempt' => $attemptid,
                            'cmid' => $this->quizobj->get_cmid(),
                            'page' => $p,
                        ]);
                        break;
                    }
                    $attemptobj->set_currentpage($p);
                }
            }

            if (is_null($nextpage)) {
                if ($pendingquestions) {
                    $slotpage = $attemptobj->get_question_page(reset($pendingquestions));
                    $attemptobj->set_currentpage($slotpage);
                }
                // If there is no next page with incomplete questions, redirect to the summary page.
                $nextpage = $attemptobj->summary_url();
            }


            $this->clean_up_session($attemptid);

            // Redirect to the next page.
            redirect($nextpage);
        }

        $uniqueid = $attemptobj->get_attempt()->uniqueid;
        $PAGE->requires->js_call_amd('quizaccess_hidecorrect/hidecorrect', 'init',  [$completed, $uniqueid, $completedquestions]);

        $this->generate_dynamic_css($completed, $uniqueid, $completedquestions);

        $this->clean_up_session($attemptid);

    }

    /**
     * Check the current page has any incomplete questions, if yes then return the list of completed questions.
     *
     * @param int $page Current page number.
     * @param quiz_attempt $attemptobj Current attempt object.
     * @param question_usage_by_activity $quba Question usage instance for this attempt.
     * @return array List of completed questions and question ids.
     */
    protected function is_page_has_incomplete_questions($page, $attemptobj, $quba) {

        $slots = $attemptobj->get_slots($page);

        if (empty($slots)) {
            $slots = $quba->get_slots();
        }
        $completed = [];
        // Hide partially correct questions.
        $hidecorrect = $this->quiz->{"hidecorrect"} ?? self::ENABLE;
        if (!empty($slots)) {
            foreach ($slots as $slot) {
                // Get the slot question previous attemp state as string in correctness. it return correct for completed questions.
                $state = $quba->get_question_state($slot, true);
                // Hide the questions answered paritialy correct.
                if ($hidecorrect == self::PARTIAL) {
                    if ($state == question_state::$gradedright || $state == question_state::$gradedpartial
                        || $state == question_state::$mangrright || $state == question_state::$mangrpartial) {
                        $completed[] = $slot;
                    }
                } else {
                    if ($state == question_state::$mangrright || $state == question_state::$gradedright) {
                        $completed[] = $slot;
                    }
                }
            }
        }

        $hasincompletequestions = (count($slots) > count($completed));

        return [$hasincompletequestions, $completed];
    }

    /**
     * Generate the dynamic css for correctly anserwed questions to hide.
     *
     * @param array $completed List of completed question ids in this page.
     * @param string $uniqueid Unique identifier for the question usage.
     * @param array $completedquestions List of completed questions in entire quiz.
     * @return void
     */
    public function generate_dynamic_css($completed, $uniqueid, $completedquestions = []) {
        global $CFG;

        // Include the css only if there are any completed questions, hide them in the question navigation panel.
        if (!empty($completedquestions)) {
            $rules = '.qnbutton#quiznavbutton' . implode(', .qnbutton#quiznavbutton', $completedquestions) . ' { display: none; }';
            $style = "section#mod_quiz_navblock { $rules }";
            $CFG->additionalhtmltopofbody .= \html_writer::tag('style', $style);
        }

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
            self::PARTIAL => get_string('hidepartiallycorrect', 'quizaccess_hidecorrect')
        ];
        $mform->addElement('select', 'hidecorrect', get_string('hidecorrect', 'quizaccess_hidecorrect'), $options);
        $mform->addHelpButton('hidecorrect', 'hidecorrect', 'quizaccess_hidecorrect');

        $options = [
            self::DISABLE => get_string('disable'),
            self::ENABLE => get_string('autogradeenable', 'quizaccess_hidecorrect'),
        ];
        $mform->addElement('select', 'hidecorrect_autograde', get_string('autograde', 'quizaccess_hidecorrect'), $options);
        $mform->addHelpButton('hidecorrect_autograde', 'autograde', 'quizaccess_hidecorrect');
        $mform->hideIf('hidecorrect_autograde', 'hidecorrect', 'eq', self::DISABLE);

        // Prevent re-attempt option.
        $options = [
            self::DISABLE => get_string('disable'),
            self::ENABLE => get_string('preventreattemptenable', 'quizaccess_hidecorrect'),
        ];
        $mform->addElement('select', 'hidecorrect_prevent_reattempt', get_string("preventreattempt", "quizaccess_hidecorrect"), $options);
        $mform->addHelpButton('hidecorrect_prevent_reattempt', 'preventreattempt', 'quizaccess_hidecorrect');
        $mform->hideIf('hidecorrect_autograde', 'hidecorrect', 'eq', self::DISABLE);

    }

    /**
     * Save the hide correct option in DB when the quiz settings form is submitted.
     *
     * @param object $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     */
    public static function save_settings($quiz) {
        global $DB;

        // Default values to false if hidecorrect property is undefined.
        $hidecorrect = $quiz->hidecorrect ?? false;
        $autograde = $quiz->hidecorrect_autograde ?? false;
        $preventreattempt = $quiz->hidecorrect_prevent_reattempt ?? false;

        $data = (object) [
            'hidecorrect' => $hidecorrect,
            'autograde' => $autograde ?: 0,
            'prevent_reattempt' => $preventreattempt ?: 0,
        ];

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
        return [
            'hidecorrect, autograde as hidecorrect_autograde, prevent_reattempt as hidecorrect_prevent_reattempt', // Select field.
            'LEFT JOIN {quizaccess_hidecorrect} hidecorrect ON hidecorrect.quizid = quiz.id', // Fetch join queyy.
            [], // Paramenters.
        ];
    }

    /**
     * Find the previous finished attempt of the user for this quiz.
     *
     * @param int $attemptid Current attempt id.
     * @return stdclass|bool Return the user previous attempt object, otherwise false.
     */
    public function user_previous_finished_attempt($attemptid) {
        global $USER;
        // Get this user's attempts.
        $attempts = quiz_get_user_attempts($this->quiz->id, $USER->id, 'finished', false);
        if (empty($attempts)) {
            return false;
        }

        $attempts = array_reverse($attempts);
        $attempt = next($attempts);

        return $attempt;
    }

    /**
     * This is called when the current attempt at the quiz is finished.
     * Update the previous attempt grades for the hidden questions.
     *
     * @return void
     */
    public function current_attempt_finished() {

        // Verify the autograde is enabled.
        if (!$this->quiz->{"hidecorrect_autograde"}) {
            return true;
        }

        if ($attemptid = optional_param('attempt', 0 , PARAM_INT)) {

            $lastattempt = $this->user_previous_finished_attempt($attemptid);
            if (!$lastattempt) {
                return false;
            }

            // Make the current attempt instance.
            $attemptobj = quiz_create_attempt_handling_errors($attemptid, $this->quizobj->get_cmid());
            // Verify the attempt contains the manaual grading questions.
            // if ($attemptobj->requires_manual_grading()) {
                // Load question usage instance for current attempt.
                $quba = question_engine::load_questions_usage_by_activity($attemptobj->get_attempt()->uniqueid);
                // Load question usage instance for previous attempt.
                $prevquba = question_engine::load_questions_usage_by_activity($lastattempt->uniqueid);

                $attempthasmanualgrade = false;
                foreach ($attemptobj->get_slots() as $slot) {
                    $qa = $quba->get_question_attempt($slot);
                    // Verify the question needs to be grade and it doesn't changed from previous attempt.
                    // echo '<pre>'; print_r($qa);
                    // $prevqstate = $prevquba->get_question_state($slot, true);
                    // Get the previous question state.
                    $prevqstate = $prevquba->get_question_state($slot, true);

                    if (($qa->get_state() == question_state::$needsgrading && $qa->get_num_steps() == 2)
                        || $prevqstate == question_state::$mangrright) {

                        // Verifiy the hide partially correct questions has been graded automatically in the new attempt.
                        $hidecorrect = $this->quiz->{"hidecorrect"};
                        $result = false;

                        if ($hidecorrect == self::PARTIAL) {
                            if ($prevqstate == question_state::$gradedright || $prevqstate == question_state::$mangrright
                                || $prevqstate == question_state::$mangrpartial || $prevqstate == question_state::$gradedpartial) {
                                $result = true;
                            }
                        } else {
                            if ($prevqstate == question_state::$gradedright || $prevqstate == question_state::$mangrright) {
                                $result = true;
                            }
                        }

                        if ($result) {
                            $comment = '';
                            $prevgradeduser = '';
                            $commentformat = '';
                            $gradedmark = $prevquba->get_question_mark($slot); // Get grade of the question from previous attempt.
                            $prevqa = $prevquba->get_question_attempt($slot); // Question attempt instance for this quetsion.

                            foreach ($prevqa->get_step_iterator() as $step) {
                                // Find the question is graded in previous attempt.
                                if ($step->get_state()->is_commented()) {
                                    $prevgradeduser = $step->get_user_id(); // Fetch the graded user.
                                    $comment = $step->get_behaviour_var('comment'); // Fetch the comment for this question.
                                    $commentformat = $step->get_behaviour_var('commentformat');
                                }
                            }

                            if ($prevgradeduser) {
                                $attempthasmanualgrade = true;
                                // This is the qustion is graded in previous attempt.
                                // Then now use the same grades and comments for this attempt.
                                $qa->manual_grade($comment, $gradedmark, $commentformat, null, $prevgradeduser);
                                $quba->get_observer()->notify_attempt_modified($qa); // Create a step for manual grade.
                            }
                        }

                    }
                }
                // Finish the grading, update the total mark for this attempt.
                if ($attempthasmanualgrade) {
                    $this->process_autograded_actions($quba, $attemptobj);
                }
            // }
        }
    }

    /**
     * Store the final grade for this attempt and regrade the attempt.
     *
     * @param question_usage_by_activity $quba
     * @param quiz_attempt $quizattempt
     * @return void
     */
    public function process_autograded_actions($quba, $quizattempt) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $timestamp = time();
        question_engine::save_questions_usage_by_activity($quba);

        $attempt = $quizattempt->get_attempt();
        $attempt->timemodified = $timestamp;
        if ($attempt->state == $quizattempt::FINISHED) {
            $attempt->sumgrades = $quba->get_total_mark();
        }

        $DB->update_record('quiz_attempts', $attempt);

        if (!$quizattempt->is_preview() && $attempt->state == $quizattempt::FINISHED) {

            if (method_exists('\mod_quiz\grade_calculator', 'recompute_final_grade')) {
                \mod_quiz\grade_calculator::create($this->quizobj)->recompute_final_grade();
            } else {
                quiz_save_best_grade($this->quiz);
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Check if the user has completed all the questions in the quiz and
     * prevent the user from starting a new attempt.
     *
     * @param int $numattempts Number of attempts made by the user.
     * @param stdClass $lastattempt Last attempt object of the user.
     * @return string|bool Returns an error message if access is prevented, otherwise false.
     */
    public function prevent_new_attempt($numattempts, $lastattempt) {
        global $OUTPUT;

        if ($numattempts == 0) {
            return false;
        }

        // Hide partially correct questions.
        $preventreattempt = $this->quiz->{"hidecorrect_prevent_reattempt"} ?? self::DISABLE;

        // Hidecorrect method.
        $hidecorrect = $this->quiz->{"hidecorrect"} ?? self::ENABLE;

        if ($preventreattempt == self::DISABLE || $hidecorrect == self::DISABLE) {
            return false;
        }

        // Load question usage instance for last attempt.
        $quba = question_engine::load_questions_usage_by_activity($lastattempt->uniqueid);
        $completedquestions = [];

        // Find the list of completed questions in this last attempt.
        foreach ($quba->get_slots() as $slot) {
            $state = $quba->get_question_state($slot, true);
            if ($hidecorrect == self::PARTIAL) {
                if ($state == question_state::$gradedright || $state == question_state::$gradedpartial
                    || $state == question_state::$mangrright || $state == question_state::$mangrpartial) {
                    $completedquestions[] = $slot;
                }
            } else {
                if ($state == question_state::$mangrright || $state == question_state::$gradedright) {
                    $completedquestions[] = $slot;
                }
            }
        }

        // If the user has answered all the questions correctly in the last attempt.
        if (count($quba->get_slots()) == count($completedquestions)) {
            return $OUTPUT->render_from_template('core/notification_warning',
                ['message' => get_string('questioncompletes', 'quizaccess_hidecorrect')]
            );
        }

        return false;
    }
}
