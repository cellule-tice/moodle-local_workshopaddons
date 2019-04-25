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

require_once($CFG->dirroot. '/mod/workshop/locallib.php');

/*
 * This function tells if a module is used into a given course
 * @global $DB
 * @global $course
 * @return boolean
 */
function workshop_is_used_in_course() {
    global $DB, $course;
    $moduleid = get_workshop_id();
    $courseinfo = $DB->get_records('course_modules', array('course' => $course->id, 'module' => $moduleid), 'id');
    return (!empty($courseinfo));
}

/*
 * Get all workshop for a given couurse
 * @global $DB
 * @global $course
 * @return object $list
 */
function get_workshop_list() {
    global $DB, $course;
    $list = $DB->get_records_select('workshop', "course='$course->id'");
    return $list;
}

/*
 * Get all submissions of a given user
 * @param int $userid
 * @global $DB
 * @global $course
 * @return array $list2
 */
function get_submission_for_user ($userid) {
    global $DB, $course;
    // Get workshop list for course.
    $list = get_workshop_list();
    $list2 = array();
    foreach ($list as $elt) {
        $workshopid = $elt->id;
        // Get submission list for user and course.
        $list2 = array_merge($list2, $DB->get_records_select('workshop_submissions',
                "workshopid='$workshopid' AND authorid='$userid'"));
    }
    return $list2;
}

/*
 * Get the submission of a user for a given workshop
 * @param int $userid
 * @param int $workshopid
 * @global $Db
 * @global $course
 * @return array
 */
function get_submission_for_user_and_workshop ($userid, $workshopid) {
     global $DB, $course;
    // Get workshop list for course.
    $list = get_workshop_list();
    $list2 = array();
    foreach ($list as $elt) {
        if ($workshopid == $elt->id) {
            // Get submission list for user and course.
            $list2 = array_merge($list2, $DB->get_records_select('workshop_submissions',
                    "workshopid='$workshopid' AND authorid='$userid'"));
        }
    }
    return $list2;
}

/*
 * Get all results for a given user of a group and a given workshop
 * @param int $userid
 * @param array $groupmembers
 * @param int $workshopid
 * @global $DB
 * @global $course
 * @return array
 */
function get_results_for_user_and_workshop ($userid, $groupmembers, $workshopid) {
    global $DB, $course;
    $nbmembers = count($groupmembers);
    $missing = array();
    $submissionlist = get_submission_for_user_and_workshop ($userid, $workshopid);
    $list = array();
    foreach ($submissionlist as $submission) {
        $assessmentlist = get_assessmementidlist_from_submissionid ($submission->id);
        $list2 = get_questionlist_for_workshopid ($workshopid);
        if (empty($assessmentlist)) {
            for ($l = 1; $l <= count($list2); $l++) {
                foreach ($groupmembers as $member) {
                    $list[$member->id][] = '-';
                }
            }
        }

        foreach ($assessmentlist as $assessment) {
            $list3 = $DB->get_records_select('workshop_grades', "assessmentid='$assessment->id'");
            if (empty($list3)) {
                if (!array_key_exists($workshopid, $missing)) {
                    $missing[$workshopid] = 1;
                } else {
                    $missing[$workshopid]++;
                }
            } else {
                foreach ($list3 as $elt) {
                    $list[$assessment->reviewerid][] = $elt->grade;
                }
            }
        }
        if (array_key_exists($workshopid, $missing)) {
            if ($missing[$workshopid] == $nbmembers) {
                for ($l = 1; $l <= count($list2); $l++) {
                    foreach ($groupmembers as $member) {
                        $list[$member->id][] = '-';
                    }
                }
            }
            $workshop = get_workshopname($workshopid);
        }
    }
    return $list;
}

/*
 * Get the name of a given workshop
 * @param int $workshopid
 * @global $DB
 * @return object $list
 */
function get_workshopname($workshopid) {
    global $DB;
    $list = $DB->get_record_select('workshop', "id='$workshopid'");
    return $list;
}

/*
 * Get all questions o a given workshop
 * @param int $workshopid
 * @global $DB
 * @return object $list
 */
function get_questionlist_for_workshopid ($workshopid) {
     global $DB;
     $list = $DB->get_records_select('workshopform_rubric', "workshopid='$workshopid'");
     return $list;
}

/*
 * Get all assesments of a given submission
 * @param int $submissionid
 * @global $DB
 * @return object $list
 */
function get_assessmementidlist_from_submissionid ($submissionid) {
     global $DB;
     $list = $DB->get_records_select('workshop_assessments', "submissionid='$submissionid'");
     return $list;
}

/*
 * Export into an excel file a tablecontent
 * @param array $tablecontent
 */
function export_workshop($tablecontent) {
    global $CFG, $course;
    require_once($CFG->libdir . '/excellib.class.php');
    require_once($CFG->libdir . '/phpexcel/PHPExcel.php');

     $filename = clean_filename($course->shortname.'_workshop_synthese.xls');
      // Creating a workbook.
    $objphpexcel = new PHPExcel();
    $objworksheet = $objphpexcel->getActiveSheet();

    // Define width of the columns.
    $objworksheet->getColumnDimension('A')->setWidth(40);
    $objworksheet->getColumnDimension('B')->setWidth(20);
    $objworksheet->getColumnDimension('C')->setWidth(20);
    $rowno = 1;
    $objworksheet->getStyle('A'.$rowno)->getFont()->setBold(true);
    $objworksheet->getStyle('A'.$rowno)->getFont()->setSize('12');
    $objworksheet->getStyle('B'.$rowno)->getFont()->setBold(true);
    $objworksheet->getStyle('B'.$rowno)->getFont()->setSize('12');
    $objworksheet->getStyle('C'.$rowno)->getFont()->setBold(true);
    $objworksheet->getStyle('C'.$rowno)->getFont()->setSize('12');
    $objworksheet->getStyle($rowno)->getFont()->setBold(true);
    $objworksheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objworksheet->getStyle('B1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objworksheet->getStyle('C1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    foreach ($tablecontent as $elt) {
        $colno = 'A';
        foreach ($elt as $content) {
            $objworksheet->fromArray(array(strval(str_replace('<br />', "\n", $content))), null, $colno.$rowno);
            $objworksheet->getStyle($colno.$rowno)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

            if (($rowno > 1) && ($colno > 'C')) {
                $objworksheet->getStyle($colno.$rowno)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            $colno++;
        }
        $rowno++;
    }
    $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    $filename = $filename.'.xlsx';

    if (is_https()) { // HTTPS sites - watch out for IE! KB812935 and KB316431.
        header('Cache-Control: max-age=10');
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header('Pragma: ');
    } else { // Normal http - prevent caching at all cost.
        header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header('Pragma: no-cache');
    }

    if (core_useragent::is_ie()) {
        $filename = rawurlencode($filename);
    } else {
        $filename = s($filename);
    }

    header('Content-Type: '.$mimetype);
    header('Content-Disposition: attachment;filename="'.$filename.'"');

    $objwriter = PHPExcel_IOFactory::createWriter($objphpexcel, 'Excel2007');
    $objwriter->setIncludeCharts(true);
    ob_end_clean();
    // Note that there is bug known with office 2013: the chart is removed.
    $objwriter->save('php://output');

    exit;
}

/*
 * Get instance of module into course_modules table corresponding to a workshop into a course
 * @param int $workshopid
 * @global $DB
 * @global $course
 * @return integer
 */
function get_related_entryid_for_workshop($workshopid) {
    global $DB, $course;
    $module = get_workshop_id ();
    $courseinfo = $DB->get_record_select('course_modules', "course='$course->id' and module='$module'"
            . " and instance = '$workshopid'");
    if (count($courseinfo)) {
        return $courseinfo->id;
    }
    return 0;
}

/*
 * Download all submissions of a given workshop
 * @param object $workshop
 * @global $CFG
 */
function download_submissions($workshop) {
    global $CFG;

    // More efficient to load this here.
    require_once($CFG->libdir.'/filelib.php');

    require_capability('mod/workshop:viewallassessments', $workshop->context);

    // Load all users with submit.
    $students = get_enrolled_users($workshop->context, "mod/workshop:submit");

    // Build a list of files to zip.
    $filesforzipping = array();
    $fs = get_file_storage();

    $groupmode = groups_get_activity_groupmode($workshop->cm);
    // All users.
    $groupid = 0;
    $groupname = '';
    if ($groupmode) {
        $groupid = groups_get_activity_group($workshop->cm, true);
        $groupname = groups_get_group_name($groupid).'-';
    }

    // Construct the zip file name.
    $filename = clean_filename($workshop->course->shortname . '-' .
                               $workshop->id . '-' .
                               $groupname.$workshop->cm->id. '.zip');

    // Get all the files for each student.

    foreach ($students as $student) {
        $userid = $student->id;

        if ((groups_is_member($groupid, $userid) or !$groupmode or !$groupid)) {
            // Get the plugins to add their own files to the zip.

            $groupname = '';
            $submission = current($workshop->get_submissions($userid, $groupid));  // Only one submission from user.

            $prefix = str_replace('_', ' ', $groupname . fullname($student));

            if ($submission) {
                if ($files  = $fs->get_area_files($workshop->context->id, 'mod_workshop', 'submission_attachment',
                        $submission->id)) {
                    // Submission is a file.
                    foreach ($files as $file) {
                        if ($file->get_mimetype() && $file->get_filename() != ".") {
                            $prefixedfilename = clean_filename($student->lastname. '_' . $student->firstname .
                                  '_' .$file->get_filename());
                            $filesforzipping[$prefixedfilename] = $file;
                        }
                    }
                } else {
                    // La soumission est sous forme de texte.
                    $temp = $workshop->get_submission_by_id($submission->id);
                    $content = $temp->content;
                    // Create file with this content.
                   // @todo.
                }
            }
        }
    }

    $result = '';
    if (count($filesforzipping) == 0) {
        $result = '';
    } else {
        $tempzip = tempnam($CFG->tempdir . '/', 'assignment_');
        // Zip files.
        $zipper = new zip_packer();
        if ($zipper->archive_to_pathname($filesforzipping, $tempzip)) {
            send_temp_file($tempzip, $filename);
            die();
        }
    }
    return $result;
}

/*
 * This function gives the id of the workshop module
 * @param string $moodulename
 * @return int  id of the module if exists, false otherwise
 */
function get_workshop_id() {
    global $DB;
    $mods = $DB->get_records('modules');
    foreach ($mods as $module) {
        if ($module->name == 'workshop') {
            return $module->id;
        }
    }
    return false;
}

function display_workshop_result_for_a_user($row, $workshoplist, $member, $groupmembers, $tablecontent, $k) {
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
        /* if (!$trouve) {
             $list2 = get_questionlist_for_workshopid ($workshopid);
             for ($i=1; $i<= count($list2); $i++) {
                  $notes[$i] = '-';
                  $details[$i][] = '';
             }
         }*/
        foreach ($notes as $key => $value) {
            $l++;
            $detail = '<div id="note'. $l . '" class="display_details"><b>';
            if ($value != '-') {
                $detail .= sprintf("%0.2f", $value / count($details[$key]));
                $tablecontent[$k][]  = sprintf("%01.2f", $value / count($details[$key]));
            } else {
                $detail .= '-';
                $tablecontent[$k][]  = '-';
            }

            $cell = new html_table_cell();
            $cell->text = $detail;
            $cell->style = "width:20px";
            $row->cells[] = $cell;
        }
    }
    return array($row, $tablecontent);
}