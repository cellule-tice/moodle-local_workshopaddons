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
 *
 * @package    local_workshopaddons
 * @copyright  2017 Laurence Dumortier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/locallib.php');

/*
 * This function is usefull to extend course navigation
 */
function local_workshopaddons_extend_navigation_course(navigation_node $parentnode, stdClass $course , context_course $context  ) {
    global $CFG;
    if (has_capability('moodle/course:update', $context) && workshop_is_used_in_course()) {
        $params = array('id' => $course->id);
        // In the report tab linked to course navigation, a node is added and entitled workshop.
        $reportnode = $parentnode->find('coursereports', null);
        $workshopnode = $reportnode->add(get_string('pluginname', 'mod_workshop'));

        // In this node, a link is added to display results linked to all workshops by groups.
        $workshopnode->add(get_string('display_results', 'local_workshopaddons'), new moodle_url(
                    '/local/workshopaddons/display_results.php', $params), navigation_node::TYPE_SETTING, null,
                'local_workshopaddons_display_results');
        // In this node, a second link is added to display full results linked to all workshops.
        $workshopnode->add(get_string('display_full_results', 'local_workshopaddons'), new moodle_url(
                    '/local/workshopaddons/display_full_results.php', $params), navigation_node::TYPE_SETTING, null,
                'local_workshopaddons_display_full_results');
    }
}

/*
 * This function is usefull to extend settings navigation
 */
function local_workshopaddons_extend_settings_navigation($settingsnav, $context) {
    global $PAGE, $COURSE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }
    if ($settingnode = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING)) {
        if ($PAGE->cm->modname == 'workshop') {
            $displaywizard = $url = get_config('local_workshopaddons', 'displaywizard');
            if ($displaywizard) {
                $url = new moodle_url('/local/workshopaddons/wizard.php', array('id' => $PAGE->cm->id));    
                $settingnode->add(get_string('wizard', 'local_workshopaddons'), $url, settings_navigation::TYPE_SETTING);
            }

            $list = explode(',', get_config('local_workshopaddons', 'courselistwithfillinsubmissionslink'));
            foreach ($list as $key => $value) {
                $list[$key] = trim($value);
            }
            $displaylink = in_array($COURSE->shortname, $list);

            if ($displaylink) {
                // A link is added to fill in all missing submissions.
                $url = new moodle_url('/local/workshopaddons/index.php', array('cmid' => $PAGE->cm->id));
                $settingnode->add(get_string('fillinallmissingsubmissions', 'local_workshopaddons'), $url,
                        settings_navigation::TYPE_SETTING);
                // A link is added for automatic allocation.
                $url = new moodle_url('/mod/workshop/allocation.php', array('cmid' => $PAGE->cm->id, 'method' => 'random'));
                $settingnode->add(get_string('pluginname', 'workshopallocation_random'), $url, settings_navigation::TYPE_SETTING);
            }
            // A link is added to download all submissions.
            $url = new moodle_url('/local/workshopaddons/index.php', array('cmid' => $PAGE->cm->id, 'cmd' => 'downloadall'));
            $settingnode->add(get_string('downloadall', 'local_workshopaddons'), $url, settings_navigation::TYPE_SETTING);
        }
    }
}