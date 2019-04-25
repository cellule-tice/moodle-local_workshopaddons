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

$courseid = optional_param('id', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

if ($courseid) {
    $course         = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
}
require_login($course, true);
require_capability('mod/workshop:viewallsubmissions', $PAGE->context);

$pageparams = array('id' => $courseid);
if ($groupid) {
    $pageparams = array_merge($pageparams, array('groupid' => $groupid));
}

if ($userid) {
     $pageparams = array_merge($pageparams, array('userd' => $userid));
}

// Print the page header.
$PAGE->set_url('/local/workshopaddons/display_results.php', array('id' => $courseid));

$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);

$PAGE->set_heading($course->fullname . ' - ' . get_string('display_results', 'local_workshopaddons'));
$PAGE->set_title($course->fullname);
$PAGE->set_url(new moodle_url('/local/workshopaddons/display_results.php', $pageparams));
$PAGE->set_pagelayout('course');


$PAGE->requires->js_call_amd('local_workshopaddons/workshop', 'display_results');

echo $OUTPUT->header();
$content = '';

$title = $course->fullname . ' - ' . get_string('display_results', 'local_workshopaddons');
$content .= html_writer::tag('h2', $title);

// Get all groups.
$groups = groups_get_all_groups($courseid);

// Display all groups.
if ($groups) {
    $content .= '<ul>';
    foreach ($groups as $group) {
        $content .= html_writer::start_tag('li', array('class' => 'memberlist', 'id' => $group->id));
        $link = '?id='.$courseid.'&groupid='.$group->id;
        $content .= html_writer::link($link,  $group->name);
        $groupmembers = groups_get_members($group->id);
        if (count($groupmembers)) {
            $content .= html_writer::start_tag('ol', array('class' => 'groupmembers hidden', 'id' => 'groupmembers'.$group->id));
            foreach ($groupmembers as $member) {
                $link2 = $link .'&userid='.$member->id;
                $content .= html_writer::tag('li', $member->firstname . ' ' . $member->lastname);
            }
            $content .= html_writer::end_tag('ol');
        }
        $content .= html_writer::end_tag('li');
    }
    $content .= '</ul>';
}
$l = 0;
if ($groupid) {
    $table = new html_table();
    $head = array(get_string('group'), get_string('lastname'), get_string('firstname'), 'user_id');

    $workshoplist = array();
    $groupmembers = groups_get_members($groupid);

    // Find all workshops of this group and find the amount of questions for this workshop.
    foreach ($groupmembers as $member) {
        // Get submissions for this user.
        $list2 = get_submission_for_user ($member->id);
        // GEt worhopsid list.
        foreach ($list2 as $info) {
            $workshopid = $info->workshopid;
            if (!in_array($workshopid, $workshoplist)) {
                $workshoplist[] = $workshopid;
            }
        }
    }
    asort($workshoplist);

    $table->head = $head;
    foreach ($groupmembers as $member) {

        $row = new html_table_row();
        $row->cells[] = $groups[$groupid]->name;
        $row->cells[] = $member->lastname;
        $row->cells[] = $member->firstname;
        $row->cells[] = $member->id;

        foreach ($workshoplist as $workshopid) {
            // Foreach workshop get results for a specific member of this group.
            $results = get_results_for_user_and_workshop ($member->id, $groupmembers, $workshopid);

            $notes = array();
            $nbval = count($results);
            $details = array();
            $trouve = false;

            foreach ($results as $reviewerid => $elt) {
                foreach ($elt as $key => $value) {
                    if (!array_key_exists($key, $notes)) {
                        $notes[$key] = 0;
                    }
                    if ($value != '-') {
                        $notes[$key] += $value;
                        $details[$key][] = sprintf("%01.2f", $value);
                    } else {
                        $notes[$key] = '-';
                        $details[$key][] = '';
                    }
                    $trouve = true;
                }
            }
            if (!$trouve) {
                $list2 = get_questionlist_for_workshopid ($workshopid);
                for ($i = 1; $i <= count($list2); $i++) {
                    $notes[$i] = '-';
                    $details[$i][] = '';
                }
            }
            foreach ($notes as $key => $value) {
                $l++;
                $detail = '<div id="note'. $l . '" class="display_details"><b>';
                if ($value != '-') {
                    $detail .= sprintf("%0.2f", $value / count($details[$key])) .
                            ' (' . $value . '/'. count($details[$key]) . ')</b></div>';

                } else {
                    $detail .= '-';
                }
                $detail .= '<br> <div class="hidden" id="detail'.$l . '" >' . implode (' - ', $details[$key]) . '</div>';
                $row->cells[] = $detail;
            }
        }
        $table->data[] = $row;
    }
    $list = array();
    foreach ($workshoplist as $workshopid) {
        $workshop = get_workshopname($workshopid);
        $toolid = get_related_entryid_for_workshop($workshopid);
        if ($toolid) {
            $list[] = html_writer::link('../../mod/workshop/view.php?id='.$toolid, $workshop->name, array('target' => '_blank'));
        }
    }
    $content .= html_writer::alist($list, array(), 'ol');
    $content .= html_writer::table($table);
}

echo $content;

// Finish the page.
echo $OUTPUT->footer();
