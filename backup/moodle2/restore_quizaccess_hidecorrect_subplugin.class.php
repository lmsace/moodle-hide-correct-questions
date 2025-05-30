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
 * Restore the configured to hide the correct questions for the quiz.
 *
 * @package    quizaccess_hidecorrect
 * @copyright  2023 LMSACE Dev Team <lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');

/**
 * Provides the information to restore the quiz data, which is configured to hide the correct questions.
 *
 * @package    quizaccess_hidecorrect
 * @copyright  2023 LMSACE Dev Team <lmsace.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quizaccess_hidecorrect_subplugin extends restore_mod_quiz_access_subplugin {

    /**
     * Use this method to describe the XML paths that store your sub-plugin's
     * settings for a particular quiz.
     */
    protected function define_quiz_subplugin_structure() {

        $paths = [];

        $elename = $this->get_namefor('');
        $elepath = $this->get_pathfor('/quizaccess_hidecorrect');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Processes the quizaccess_hidecorrect element restore.
     *
     * @param array $data the data read from the XML file.
     */
    public function process_quizaccess_hidecorrect($data) {
        global $DB;

        $data = (object)$data;
        $data->quizid = $this->get_new_parentid('quiz');
        $DB->insert_record('quizaccess_hidecorrect', $data);
    }
}
