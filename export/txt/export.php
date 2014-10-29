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

require_once '../../../../config.php';
global $CFG, $DB;
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'checkoutcome_grade_export_txt.php';

$id                = required_param('id', PARAM_INT); // course id
$groupid           = optional_param('groupid', 0, PARAM_INT);
$itemids           = required_param('itemids', PARAM_RAW);
$export_counter   = optional_param('export_counter', 0, PARAM_BOOL);
$export_feedback   = optional_param('export_feedback', 0, PARAM_BOOL);
$separator         = optional_param('separator', 'comma', PARAM_ALPHA);
$updatedgradesonly = optional_param('updatedgradesonly', false, PARAM_BOOL);
$gradetype       = optional_param('gradetype', 0, PARAM_INT);
$decimalpoints     = optional_param('decimalpoints', $CFG->grade_export_decimalpoints, PARAM_INT);
$gradesource      = optional_param('gradesource', 0, PARAM_INT);
$itemmodule			= optional_param('itemmodule', null, PARAM_ALPHA);
$iteminstance			= optional_param('iteminstance', null, PARAM_INT);
$checkoutcomeid			= optional_param('checkoutcomeid', null, PARAM_INT);
$selected_periodid		= optional_param('periodide', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_COURSE, $id);
}
else {
	$context = context_course::instance($id);
}

//require_capability('moodle/grade:export', $context);
//require_capability('gradeexport/txt:view', $context);

if (groups_get_course_groupmode($COURSE) == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
    if (!groups_is_member($groupid, $USER->id)) {
        print_error('cannotaccessgroup', 'grades');
    }
}

// print all the exported data here
$export = new checkoutcome_grade_export_txt($course, $groupid, $itemids, $export_counter, $export_feedback, $updatedgradesonly, $gradetype, $decimalpoints, $gradesource, $separator, $itemmodule, $iteminstance, $checkoutcomeid,$selected_periodid);
$export->print_grades();


