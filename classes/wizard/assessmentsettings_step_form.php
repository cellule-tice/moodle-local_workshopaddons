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
 * This file defines the class for editing the assessment settings form.
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
 * The class for editing the assessment settings form.
 *
 * @package    mod_workshop
 * @author     Gilles-Philippe Leblanc <gilles-philippe.leblanc@umontreal.ca>
 * @author     Issam Taboubi <issam.taboubi@umontreal.ca>
 * @copyright  2017 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessmentsettings_step_form extends step_form {

    /**
     * The step form definition.
     */
    public function step_definition() {
        $mform = $this->_form;

        // Instructions for reviewers.
        $label = get_string('instructreviewers', 'workshop');
        $mform->addElement('editor', 'instructreviewerseditor', $label, null,
                            \workshop_wizard::instruction_editors_options($this->workshop->context));
        // Assessment start date.
        $label = get_string('assessmentstart', 'workshop');
        $mform->addElement('date_time_selector', 'assessmentstart', $label, array('optional' => true));

        // Assessment end date.
        $label = get_string('assessmentend', 'workshop');
        $mform->addElement('date_time_selector', 'assessmentend', $label, array('optional' => true));
    }

    /**
     * Validates the form input
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array eventual errors indexed by the field name
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($errors)) {
            return $errors;
        }
        $data['submissionstart'] = $this->workshop->submissionstart;
        $data['submissionend'] = $this->workshop->submissionend;
        if (!isset($data['assessmentstart'])) {
            $data['assessmentstart'] = 0;
        }

        // Check the phases borders are valid.
        if ($data['assessmentstart'] > 0 and $data['assessmentend'] > 0 and $data['assessmentstart'] >= $data['assessmentend']) {
            $errors['assessmentend'] = get_string('assessmentendbeforestart', 'local_workshopaddons');
            return $errors;
        }

        // Check the phases do not overlap.
        if (max($data['submissionstart'], $data['submissionend']) > 0
            && max($data['assessmentstart'], $data['assessmentend']) > 0) {
            $phasesubmissionend = max($data['submissionstart'], $data['submissionend']);
            $phaseassessmentstart = min($data['assessmentstart'], $data['assessmentend']);
            if ($phaseassessmentstart == 0) {
                $phaseassessmentstart = max($data['assessmentstart'], $data['assessmentend']);
            }
            if ($phasesubmissionend > 0 && $phaseassessmentstart > 0 && $phaseassessmentstart < $phasesubmissionend) {
                foreach (array('assessmentend', 'assessmentstart') as $f) {
                    if ($data[$f] > 0) {
                        $errors[$f] = get_string('phasesoverlap', 'local_workshopaddons');
                        break;
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * Prepares the form before data are set
     *
     * Additional wysiwyg editor are prepared here, the introeditor is prepared automatically by core.
     * Grade items are set here because the core modedit supports single grade item only.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$data) {
            // Editing an existing workshop - let us prepare the added editor elements (intro done automatically).
            $draftitemid = file_get_submitted_draft_itemid('instructreviewers');
            $data['instructreviewerseditor']['text'] = file_prepare_draft_area($draftitemid, $this->workshop->context->id,
                                'mod_workshop', 'instructreviewers', 0,
                                \workshop_wizard::instruction_editors_options($this->workshop->context),
                                $data['instructreviewers']);
            $data['instructreviewerseditor']['format'] = $data['instructreviewersformat'];
            $data['instructreviewerseditor']['itemid'] = $draftitemid;
    }

}
