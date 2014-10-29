<?php
// This file is part of the Checklist plugin for Moodle - http://moodle.org/
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
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

global $DB;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$checkoutcomeid  = optional_param('checkoutcome', 0, PARAM_INT);  // checklist instance ID


$url = new moodle_url('/mod/checkoutcome/update_period.php');
if ($id) {
	if (!$cm = get_coursemodule_from_id('checkoutcome', $id)){
		print_error('error_cmid', 'checkoutcome'); // 'Course Module ID was incorrect'
	}
	$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	$checkoutcome = $DB->get_record('checkoutcome', array('id' => $cm->instance), '*', MUST_EXIST);
	$url->param('id', $id);
} else if ($checkoutcomeid) {
	$checkoutcome = $DB->get_record('checkoutcome', array('id' => $checkoutcomeid), '*', MUST_EXIST);
	$course = $DB->get_record('course', array('id' => $checkoutcome->course), '*', MUST_EXIST);
	if (!$cm = get_coursemodule_from_instance('checkoutcome', $checkoutcome->id, $course->id)) {
		print_error('error_cmid', 'checkoutcome'); // 'Course Module ID was incorrect'
	}
	$url->param('checkoutcome', $checkoutcomeid);
} else {
	print_error('error_specif_id', 'checkoutcome'); // 'You must specify a course_module ID or an instance ID'
}

require_login($course, true, $cm);
$PAGE->set_url($url);
$PAGE->requires->css('/mod/checkoutcome/styles.css');


if ($chk = new checkoutcome_class($cm->id, 0, $checkoutcome, $cm, $course)) {
	$chk->update_period();
}