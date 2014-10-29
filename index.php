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
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// Replace checkoutcome with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

global $DB, $PAGE, $OUTPUT, $CFG, $USER;

$id = required_param('id', PARAM_INT);   // course

$table = new html_table();

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

if ($CFG->version > 2014051200) { // Moodle 2.7+
    $params = array(
        'context' => context_course::instance($course->id)
    );
    $event = \mod_checklist\event\course_module_instance_list_viewed::create($params);
    $event->add_record_snapshot('course', $course);
    $event->trigger();
} else { // Before Moodle 2.7
	add_to_log($course->id, 'checkoutcome', 'view all', 'index.php?id='.$course->id, '');
}

if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
} else {
    $context = context_course::instance($course->id);
}


$PAGE->set_url('/mod/checkoutcome/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);


echo $OUTPUT->header();

if (! $checkoutcomes = get_all_instances_in_course('checkoutcome', $course)) {
    notice(get_string('nocheckoutcomes', 'checkoutcome'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}

foreach ($checkoutcomes as $checkoutcome) {
    if (!$checkoutcome->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/checkoutcome.php', array('id' => $checkoutcome->coursemodule)),
            format_string($checkoutcome->name, true),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/checkoutcome.php', array('id' => $checkoutcome->coursemodule)),
            format_string($checkoutcome->name, true));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($checkoutcome->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'checkoutcome'), 2);
echo html_writer::table($table);
echo $OUTPUT->footer();
