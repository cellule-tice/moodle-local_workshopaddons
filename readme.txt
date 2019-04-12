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
 * @copyright  2017 Laurence Dumortier - Cellule TICE - UniversitÃ© de Namur
 *  Wizard :  
 * @author     Gilles-Philippe Leblanc <gilles-philippe.leblanc@umontreal.ca>
 * @copyright  2017 Université de Montréal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


This plugin was developped in the case of intensive use (several times) of the workshop plugin for groups. 

The worskhop tool was used at first to do pear evaluation in a group without evaluation of an assignment. 
The evaluation was based on how students are working together and how tasks are completed correctly.

The first functionnality developped to that goal was to fill in all missing submission in a workshop. 
In that aim, in a workshop, a new command is available in the settings menu to fill in all missing submission. 
For each student that haven't yet submit a work, a new record is added in database with the name of the worskop 
with a "blank" content.


The second functionnality was developped to get a display of all the results of the workshops without going 
into all workshops to collect results. 
Two views are avalaible, the first one is a display by group, the second one is the display for all the students in groups. 
For each view, an export into an excel file is available.

A third functionnality gives the opportunity to download all submissions.

A wizard is available. This tool was develped by University of Montreal inside the mod/assign tool. 
Laurence Dumortier extracts the functionnalities developed for this wiard and insert them in a local worskhop add on tool.
