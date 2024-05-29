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
 * Quiz access hide correct questions - Upgrade script
 *
 * @package    quizaccess_hidecorrect
 * @subpackage hidecorrect
 * @copyright  2023 LMSACE Dev Team.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Upgrade database to support autograde feature.
  *
  * @param int $oldversion
  * @return void
  */
function xmldb_quizaccess_hidecorrect_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023091400) {
        // Add a new column 'autograde' to the hidecorrect table.
        $table = new xmldb_table('quizaccess_hidecorrect');

        // Define field autograde to be added to hidecorrect.
        $field = new xmldb_field('autograde', XMLDB_TYPE_INTEGER, 9, null, null, null);

        // Conditionally launch add field autograde.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2023091400, 'quizaccess', 'hidecorrect');
    }
    return true;
}
