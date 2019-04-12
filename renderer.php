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
 * Workshop module renderering methods are defined here
 *
 * @package    mod_workshop
 * @copyright  2009 David Mudrak <david.mudrak@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/mod/workshop/renderer.php');

use core\output\notification;

/**
 * Workshop module renderer class
 *
 * @copyright 2009 David Mudrak <david.mudrak@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_workshopaddons_renderer extends mod_workshop_renderer  {


    /**
     * Renders the wizard button.
     *
     * @param moodle_url $url The wizard url
     * @return string HTML
     */
    public function render_workshop_wizard_button(moodle_url $url) {
        $text = get_string('opensetupwizard', 'local_workshopaddons');
        $attributes = array('class' => 'wizard-button btn btn-primary group');
        return html_writer::div(html_writer::link($url, $text, $attributes), 'clearfix');
    }

    /**
     * Defer to template.
     *
     * @param manage_competency_frameworks_page $page
     * @return string html for the page
     */
    public function render_workshop_wizard_navigation_page(local_workshopaddons\output\wizard_navigation_page $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_workshopaddons/wizard_navigation_page', $data);
    }


}
