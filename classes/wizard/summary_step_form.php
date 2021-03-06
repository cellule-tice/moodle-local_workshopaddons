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
 * This file defines a base class for the summary form.
 *
 * @package    mod_workshop
 * @author     Gilles-Philippe Leblanc <gilles-philippe.leblanc@umontreal.ca>
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2017 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_workshopaddons\wizard;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * The class for editing the summary form.
 *
 * @package    mod_workshop
 * @author     Gilles-Philippe Leblanc <gilles-philippe.leblanc@umontreal.ca>
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2017 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary_step_form extends step_form {

    /**
     * The step form definition.
     */
    public function step_definition() {
        global $USER;
        $mform = $this->_form;
        $record = $this->workshop->get_record();
        // Summary container.
        $mform->addElement('html', \html_writer::start_div('wizard-summary'));
        // Assessment type.
        if ($record->assessmenttype == \workshop_wizard::PEER_ASSESSMENT) {
            $assessmenttype = get_string('peerassessment', 'local_workshopaddons');
        } else if ($record->assessmenttype == \workshop_wizard::SELF_ASSESSMENT) {
            $assessmenttype = get_string('selfassessment', 'local_workshopaddons');
        } else if ($record->assessmenttype == \workshop_wizard::SELF_AND_PEER_ASSESSMENT) {
            $assessmenttype = get_string('selfandpeerassessment', 'local_workshopaddons');
        }
        $mform->addElement('static', 'summary_assessmenttype', get_string('assessmenttype', 'local_workshopaddons'),
                $assessmenttype);
        // Grading method.
        $strategieslist = \workshop_wizard::available_strategies_list();
        $gradingmethod = $strategieslist[$record->strategy];
        $mform->addElement('static', 'summary_strategy', get_string('strategy', 'workshop'), $gradingmethod);

        if (property_exists('workshop', 'allowsubmission')) {
            // Allow submission.
            if ($record->allowsubmission == 1) {
                $mform->addElement('static',
                    'summary_allowsubmission', get_string('allowsubmission', 'workshop'), get_string('yes'));
            }
        }
        // Submission start.
        if ($record->submissionstart != 0) {
            $strdatestring = get_string('strftimerecentfull', 'langconfig');
            $date = userdate($record->submissionstart, $strdatestring);
            $mform->addElement('static', 'summary_submissionstart', get_string('submissionstart', 'workshop'), $date);
        }
        // Submissions deadline.
        if ($record->submissionend != 0) {
            $strdatestring = get_string('strftimerecentfull', 'langconfig');
            $date = userdate($record->submissionend, $strdatestring);
            $mform->addElement('static', 'summary_submissionend', get_string('submissionend', 'workshop'), $date);
        }
        // Phase switch assessment.
        if ($record->submissionend != 0 && $record->phaseswitchassessment != 0) {
            $mform->addElement('static',
                'summary_switchassessment', get_string('submissionendswitch', 'workshop'), get_string('yes'));
        }
        if (property_exists('workshop', 'assessassoonsubmitted')) {
            // Allow assessment after submission.
            if ($record->assessassoonsubmitted != 0) {
                $mform->addElement('static',
                    'summary_assessassoonsubmitted', get_string('assessassoonsubmitted', 'workshop'), get_string('yes'));
            }
        }
        // Peer allocation.
        $userplan = new \workshop_user_plan($this->workshop, $USER->id);
        $phase = \workshop_wizard::PHASE_SUBMISSION;
        if (!isset($userplan->phases[\workshop_wizard::PHASE_SUBMISSION])) {
            $phase = \workshop_wizard::PHASE_SETUP;
        }
        if (isset($userplan->phases[$phase])
            && isset($userplan->phases[$phase]->tasks)
            && isset($userplan->phases[$phase]->tasks['allocate'])) {
            $details = $userplan->phases[$phase]->tasks['allocate']->details;
            $mform->addElement('static', 'summary_peerallocationdetails', get_string('allocate', 'workshop'), $details);
        }

        // Check if anonimity is already in use in standard workshop ?
        if (!$this->workshop->is_self_assessment_type()) {
            // Assess without submission.
            if (property_exists('workshop', 'allowsubmission')) {
                if ($record->allowsubmission) {
                    $yesno = $record->assesswithoutsubmission ? get_string('yes') : get_string('no');
                    $mform->addElement('static',
                       'summary_assesswithoutsubmission', get_string('assesswithoutsubmission', 'workshop'), $yesno);
                }
            }
        }

        // Assessment start.
        if ($record->assessmentstart != 0) {
            $strdatestring = get_string('strftimerecentfull', 'langconfig');
            $date = userdate($record->assessmentstart, $strdatestring);
            $mform->addElement('static', 'summary_assessmentstart', get_string('assessmentstart', 'workshop'), $date);
        }

        // Assessment end.
        if ($record->assessmentend != 0) {
            $strdatestring = get_string('strftimerecentfull', 'langconfig');
            $date = userdate($record->assessmentend, $strdatestring);
            $mform->addElement('static', 'summary_assessmentend', get_string('assessmentend', 'workshop'), $date);
        }

        $mform->addElement('html', \html_writer::end_div());

    }
}
