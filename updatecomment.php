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

global $DB, $CFG;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$checkoutcomeid  = optional_param('checkoutcome', 0, PARAM_INT);  // checkoutcome instance ID
$studentid = optional_param('studentid', 0, PARAM_INT);
$itemid = optional_param('itemid', false, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$periodid = optional_param('selected_periodid', 0, PARAM_INT);

$url = new moodle_url('/mod/checkoutcome/view.php');
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

if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}

$userid = $USER->id;
if (!has_capability('mod/checkoutcome:updateown', $context) && !has_capability('mod/checkoutcome:updateother', $context)) {
    echo get_string('error_update', 'checkoutcome'); // 'Error: you do not have permission to update this checklist';
    die();
}

if (!$itemid) {
    echo get_string('error_itemid', 'checkoutcome'); // 'Error: invalid (or missing) items list';
    die();
}
if ($itemid) {
    $chk = new checkoutcome_class($cm->id, $userid, $checkoutcome, $cm, $course, $studentid, $group, $periodid);
    $chk->view();
}

