<?php

/*
 * Unused ?
 */
function get_workshop_name_list () {
    $list = get_workshop_list();
    $workshopnamelist = array();
    foreach ($list as $elt) {
        if (!in_array($elt->name, $workshopnamelist)) {
            $workshopnamelist[$elt->id] = $elt->name;
        }
    }
    return $workshopnamelist;
}


function get_results_for_submission_for_user ($userid, $groupmembers) {
    global $DB, $course;
    $nbmembers = count($groupmembers);
    $missing = array();
    $submissionlist = get_submission_for_user ($userid);
    $list = array();
    foreach ($submissionlist as $submission) {
        $workshop = get_workshopname($submission->workshopid);
        $assessmentlist = get_assessmementidlist_from_submissionid ($submission->id);
        $list2 = get_questionlist_for_workshopid ($submission->workshopid);
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
                if (!array_key_exists($workshop->id, $missing)) {
                    $missing[$workshop->id] = 1;
                } else {
                    $missing[$workshop->id]++;
                }
            } else {
                foreach ($list3 as $elt) {
                    $list[$assessment->reviewerid][] = $elt->grade;
                }
            }
        }
        if ($missing[$workshop->id] == $nbmembers) {
            for ($l = 1; $l <= count($list2); $l++) {
                foreach ($groupmembers as $member) {
                    $list[$member->id][] = '-';
                }
            }
            $workshop = get_workshopname($workshop->id);
        } else if (is_admin()) { 
        }
    }
    return $list;
}
