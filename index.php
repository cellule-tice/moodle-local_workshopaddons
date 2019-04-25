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


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot . '/mod/workshop/locallib.php');

$cmid = optional_param('cmid', 0, PARAM_INT);
$cmd          = optional_param('cmd', null, PARAM_CLEAN);

if ($cmid) {
    $cm             = get_coursemodule_from_id('workshop', $cmid, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $workshoprecord = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);
}

require_login($course, true, $cm);
require_capability('mod/workshop:viewallsubmissions', $PAGE->context);

$workshop = new workshop($workshoprecord, $cm, $course);

// Print the page header.
$PAGE->set_url('/local/workshopaddons/index.php', array('cmid' => $cm->id, 'cmd' => $cmd));

$PAGE->set_title($workshop->name);
$PAGE->set_heading($course->fullname);

$content = '';
if ($cmd == 'downloadall') {
    $res .= download_submissions($workshop);
    if ($res != '') {
        echo $res;
    } else {
        echo $OUTPUT->header();
        echo get_string('nosubmissions', 'workshop');
    }
} else {
    echo $OUTPUT->header();
    $workshoptitle = $workshop->name;
    // Get all members of this group.
    $participants = $workshop->get_participants();

    // Get all participants with a submission related to this workshop.
    $alreadysubmittedparticipants = $workshop->get_participants(true);

    // Foreach participant, if he has not yet submit, submit a simulated work with the name of the workshop?
    foreach ($participants as $userid => $elt) {
        if (!array_key_exists($userid, $alreadysubmittedparticipants)) {
            $content .= '<br> Insertion of ' . $elt->lastname . ' ' . $elt->firstname . '  le travail de titre '. $workshop->name;
            $data = new stdClass();
            $data->workshopid = $workshop->id;
            $data->title = $workshop->name;
            $data->authorid = $userid;
            $data->timecreated = time();
            $data->timemodified = time();
            $DB->insert_record('workshop_submissions', $data);
        }
    }
    if (count($participants) == count($alreadysubmittedparticipants)) {
        $content .= $OUTPUT->notification('Aucun travail ne doit être ajouté !', 'info');
    }
}

echo $content;

// Finish the page.
echo $OUTPUT->footer();
