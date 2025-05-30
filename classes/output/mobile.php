<?php
// This file is part of the deferred all or nothing question behaviour for Moodle
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
 * Mobile output class for Quiz access hide correct questions.
 *
 * @package    quizaccess_hidecorrect
 * @subpackage hidecorrect
 * @copyright  2023 LMSACE Dev Team.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_hidecorrect\output;

/**
 * Mobile output class for Quiz access hide correct questions.
 */
class mobile {

    /**
     * Returns an empty template for quizaccess_hidecorrect, Currently not supported.
     *
     * Its purpose is to provide a placeholder for future mobile support.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and otherdata
     */
    public static function mobile_quizaccess_hidecorrect($args) {

        return [
            "templates" => [
                [
                    "id" => "hidecorrect",
                    "html" => "",
                ],
            ],
            "javascript" => "",
            "otherdata" => "",
            "files" => [],
        ];
    }
}
