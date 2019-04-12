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



defined('MOODLE_INTERNAL') || die();


class workshop_wizard extends workshop {
    
    /** @var string PEER_ASSESSMENT Value for peer asssessment */
    const PEER_ASSESSMENT = 1;

    /** @var string SELF_ASSESSMENT Value for self asssessment */
    const SELF_ASSESSMENT = 2;

    /** @var string SELF_AND_PEER_ASSESSMENT Value for self and peer asssessment */
    const SELF_AND_PEER_ASSESSMENT = 3;

    /** @var string SKIP_ALLOCATION_ACTION Action that indicate to constructor to skip allocation */
    const SKIP_ALLOCATION_ACTION = "skip_allocation";
    
    /** @var string wizard step */
    public $wizardstep = null;
    
        /** @var array field names from {workshop} table */
    protected $fieldnames;
    
    /** @var int indicate who should assess the workshop */
    public $assessmenttype;
    
        /** @var bool indicate if user will submit work for workshop */
    public $allowsubmission;

    /** @var bool indicate if assessmnent is allowed as soon work is submitted */
    public $assessassoonsubmitted;

    /** @var int 1 if user can assess without submission */
    public $assesswithoutsubmission;

    /** @var boolean true if allocations already generated */
    public static $allocationsgenerated = false;

    /** @var boolean true if submissions already generated */
    public static $submissionsgenerated = false;
    
    public function __construct($workshop, $cm, $course) {
        parent::__construct($workshop, $cm, $course);
        $this->fieldnames = array();

        foreach ($workshop as $field => $value) {
            if (property_exists('workshop_wizard', $field)) {
                $this->fieldnames[] = $field;
                $this->{$field} = $value;
            }
        }
        $this->course = $course;
        
        $field = 'assessmenttype';
        $this->fieldnames[] = $field;
        if ($this->usepeerassessment && $this->useselfassessment) {    
            $this->{$field} = 3;         
        } else if ($this->usepeerassessment) {
             $this->{$field} = 1; 
        } else {
             $this->{$field} = 2; 
        }
        
        $field = 'allowsubmission';
        $this->fieldnames[] = $field;
        $this->{$field} = 1; 
        // @todo
        
        
        $field = 'assessassoonsubmitted';
        $this->fieldnames[] = $field;
        $this->{$field} = 0; 
        // @todo
        
        
        $field = 'assesswithoutsubmission';
        $this->fieldnames[] = $field;
        // @todo
        
        /*if ($cm instanceof cm_info) {
            $this->cm = $cm;
        } else {
            $modinfo = get_fast_modinfo($course);
            $this->cm = $modinfo->get_cm($cm->id);
        }
        if (is_null($cm)) {
            $this->context = context_module::instance($this->cm->id);
        } else {
            $this->context = $cm;
        }*/

       /* if (self::$allocationsgenerated == false && !self::is_assessmenttype_disabled($this->id) &&
                (!in_array(self::SKIP_ALLOCATION_ACTION, $actions))) {
            if ($this->assessmenttype == self::PEER_ASSESSMENT) {
                $this->remove_allocations(true);
            } else {
                $this->generate_self_allocations();
                if ($this->assessmenttype == self::SELF_ASSESSMENT) {
                    $this->remove_allocations(false);
                }
            }
            self::$allocationsgenerated = true;
        }

        // Generate fake submissions.
        if (self::$submissionsgenerated == false) {
            $this->generate_submissions();
            self::$submissionsgenerated = true;
        }*/
    }
    
        /**
     * Get the wizard page url.
     *
     * @param string|null $step The current step name of the wizard
     * @return moodle_url to the wizard page
     */
    public function wizard_url($step = null) {
        $params = array('id' => $this->cm->id);
        if (!empty($step)) {
            $params['step'] = $step;
        } else if (!empty($this->wizardstep)) {
            $params['step'] = $this->wizardstep;
        }
        return new moodle_url('/local/workshopaddons/wizard.php', $params);
    }
    
    /**
     * Return an instance of a wizard step class.
     *
     * @param string|null $step A Wizard step
     * @return wizard_step Instance of wizard step
     */
    public function wizard_step_instance($step = null) {
        if (empty($step)) {
            $step = $this->wizardstep;
        }
        $classname = $this->get_validated_wizard_class_name($step . '_step', 'step');
        $wizardstep = new $classname($this);
        return $wizardstep;
    }
    
    /**
     * Get a validated wizard class name.
     *
     * @param string $name The name of the class
     * @param string $basename The name of the base class
     * @return string The complete, validated class name
     * @throws \invalid_parameter_exception
     */
    public function get_validated_wizard_class_name($name, $basename) {
        $namespace = '\\local_workshopaddons\\wizard\\';
        $name = $namespace . $name;
        $basename = $namespace . $basename;
        if (!class_exists($name) || !is_subclass_of($name, $basename)) {
            $message = "Specified class $name does not exist or is not a subclass of $basename";
            throw new \invalid_parameter_exception($message);
        }
        return $name;
    }

    
    /**
     * Check if assessmenttype parameter can be modified or not for the workshop
     *
     * @param int $id ID of workshop.
     * @return bool
     */
    public static function is_assessmenttype_disabled($id) {
        global $DB;

        $params = array('workshopid' => $id);
        $submissionsql = "SELECT COUNT(s.id)
                            FROM {workshop_submissions} s
                           WHERE s.example = 0 AND s.workshopid = :workshopid";
                           //  AND s.realsubmission = 1";
        $assessmentsql = "SELECT COUNT(a.id)
                            FROM {workshop_assessments} a
                            JOIN {workshop_submissions} s ON (a.submissionid = s.id)
                           WHERE s.example = 0 AND s.workshopid = :workshopid
                             AND a.grade IS NOT NULL";
        if ($DB->count_records_sql($submissionsql, $params) || $DB->count_records_sql($assessmentsql, $params)) {
            return true;
        }

        return false;
    }
    
      /**
     * Get the record format of the workshop database based on the field.
     *
     * @return stdClass Workshop instance data from {workshop} table
     */
    public function get_record() {
        $record = new stdClass(); 
        foreach ($this->fieldnames as $field) {
            $value = $this->{$field};
            if (property_exists('workshop_wizard', $field) && is_scalar($value)) {
                $record->{$field} = $value;
            }
        }
        // Get back the course id instead of the course object;
        $record->course = $this->course->id;
        return $record;
    }
    
    /**
     * Check if the assessment type is self assessment.
     *
     * @return boolean If the assessment type is self assessment
     */
    public function is_self_assessment_type() {
        return $this->assessmenttype == self::SELF_ASSESSMENT;
    }
    
        /**
     * Check if allowsubmission parameter can be modified or not for the workshop
     *
     * @param stdClass $workshop workshop info.
     * @return bool
     */
    public static function is_allowsubmission_disabled($workshop) {
        global $DB;

        $params = array('workshopid' => $workshop->id);
        $sql = "SELECT COUNT(s.id)
                  FROM {workshop_submissions} s
                 WHERE s.example = 0 AND s.workshopid = :workshopid
                   AND s.realsubmission = 1";

        if ($workshop->phase != self::PHASE_SETUP || $DB->count_records_sql($sql, $params)) {
            return true;
        }

        return false;
    }
    
    /**
     * Generate fakes submissions for users.
     */
    public function generate_submissions() {
        global $DB;
        $users = $this->get_potential_authors(false);
        $submissions = $this->get_submissions('all', 0, 0, 0, false);
        $authors = array_map(function($o) {
            return $o->authorid;
        }, $submissions);
        $misseduserssubmissions = array_diff(array_keys($users), array_values($authors));
        $missedusers = array_diff(array_values($authors), array_keys($users));

        if (!empty($misseduserssubmissions)) {
            foreach ($misseduserssubmissions as $user) {
                    $record = new \stdClass();
                    $record->workshopid     = $this->id;
                    $record->example        = 0;
                    $record->authorid       = $user;
                    $record->timecreated    = time();
                    $record->timemodified   = 0;
                    $record->realsubmission = 0;
                    $record->contentformat  = FORMAT_HTML;
                    $record->feedbackauthorformat = editors_get_preferred_format();
                    $DB->insert_record('workshop_submissions', $record);
            }
        }

        if (!empty($missedusers)) {
            // Delete fake submission for this user.
            foreach ($missedusers as $user) {
                $submission = $submissions[array_search($user, $authors)];
                if (property_exists($submission, 'realsubmission')) {
                    if ($submission->realsubmission == 0) {
                        $DB->delete_records("workshop_submissions", array('id' => $submission->id));
                    } 
                }
            }
        }
    }
    /**
     * Remove users assessment allocations.
     *
     * @param bool $self Indicate if we remove self or other allocations
     */
    public function remove_allocations($self = true) {
        $users = $this->get_potential_authors(false);
        foreach ($users as $user) {
            $submission = $this->get_submission_by_author($user->id, false);
            if ($submission) {
                $assessmentlist = $this->get_assessments_of_submission($submission->id);
                foreach ($assessmentlist as $assessment) {
                    if (($self && $assessment->reviewerid == $user->id) || (!$self && $assessment->reviewerid != $user->id)) {
                        $this->delete_assessment($assessment->id);
                    }
                }
            }
        }
    }

}