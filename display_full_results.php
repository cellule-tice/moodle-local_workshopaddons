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
$cmd = optional_param('cmd', '', PARAM_TEXT);

if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
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

$PAGE->set_heading($course->fullname . ' - ' . get_string('display_full_results', 'local_workshopaddons'));
$PAGE->set_title($course->fullname);
$PAGE->set_url(new moodle_url('/local/workshopaddons/display_full_results.php', $pageparams));
$PAGE->set_pagelayout('course');


$PAGE->requires->js_call_amd('local_workshopaddons/workshop', 'display_results');


$content = '';

$title = $course->fullname . ' - ' . get_string('display_full_results', 'local_workshopaddons');
$export = html_writer::link('?id='.$courseid.'&cmd=export', $OUTPUT->pix_icon('i/export',
        get_string('exportresults', 'local_eee')));
$title .= ' ' .$export;

$content .= html_writer::tag('h2', $title);

// Obtenir les groupes.
$groups = groups_get_all_groups($courseid);
$l = 0;

// Afficher les groupes.
if ($groups) {
    $tablecontent = array();
    $head = array(get_string('group'), get_string('lastname'), get_string('firstname'));
    $tablecontent[0] = $head;
    $k = 1;
    foreach ($groups as $group) {
        $groupid = $group->id;
        $table = new html_table();
        $groupmembers = groups_get_members($group->id);
        if (count($groupmembers)) {
            $workshoplist = array();
            // Trouver les ateliers du groupe et le nombre de questions par atelier.
            foreach ($groupmembers as $member) {
                // Obtenir la liste des soumissions d'un utilisateur.
                $list2 = get_submission_for_user ($member->id);
                foreach ($list2 as $info) {
                    $workshopid = $info->workshopid;
                    if (!in_array($workshopid, $workshoplist)) {
                        $workshoplist[] = $workshopid;
                    }
                }
            }

            foreach ($groupmembers as $member) {
                $row = new html_table_row();
                $cell = new html_table_cell();
                $cell->text = $groups[$groupid]->name;
                $cell->style = "width:30px";
                $row->cells[] = $cell;

                $cell = new html_table_cell();
                $cell->text = $member->lastname;
                $cell->style = "width:50px";
                $row->cells[] = $cell;

                $cell = new html_table_cell();
                $cell->text = $member->firstname;
                $cell->style = "width:30px";
                $row->cells[] = $cell;

                $cell = new html_table_cell();
                $cell->text = $member->id;
                $cell->style = "width:10px";
                $row->cells[] = $cell;
                $tablecontent[$k][] = $group->name;
                $tablecontent[$k][] = $member->lastname;
                $tablecontent[$k][] = $member->firstname;

                $list($row, $tablecontent) = display_workshop_result_for_a_user($row, $workshoplist, $member, $groupmembers, $tablecontent, $k);
                $table->data[] = $row;
                $k++;
            }
        }
        $content .= html_writer::table($table);
    }
    if ($cmd == 'export') {
        $content .= export_workshop($tablecontent);
    }
}

echo $OUTPUT->header();

echo $content;

// Finish the page.
echo $OUTPUT->footer();
