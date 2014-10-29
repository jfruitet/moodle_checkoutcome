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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir.'/formslib.php';

class checkoutcome_grade_export_form extends moodleform {
    function definition() {
        global $CFG, $COURSE, $USER, $DB;

        $mform =& $this->_form;
        if (isset($this->_customdata)) {  // hardcoding plugin names here is hacky
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        $mform->addElement('header', 'options', get_string('options', 'grades'));
        
        // Period to be exported
        if (!empty($features['periods'])) {
        	$options = array();
			$options[0]=get_string('all_periods', 'checkoutcome');
        	foreach ($features['periods'] as $period) {
        		$options[$period->id] = $period->name;
        	}
        	$mform->addElement('select', 'selected_periodid', get_string('period', 'checkoutcome'), $options);
        }
        
        // Grades to be exported
        $options = array(0 => get_string('teacher_only', 'checkoutcome'),
        		1     => get_string('student_only', 'checkoutcome'),
        		2	=> get_string('teacher_student','checkoutcome'));
        
        $mform->addElement('select', 'gradesource', get_string('gradeexportsource', 'checkoutcome'), $options);
        $mform->setDefault('gradetype', GRADE_TYPE_VALUE);
        
        // Grade Type for the export
        $options = array(GRADE_TYPE_VALUE => get_string('value', 'checkoutcome'),
        		GRADE_TYPE_SCALE     => get_string('scale', 'checkoutcome'));
        
        $mform->addElement('select', 'gradetype', get_string('gradeexporttype', 'checkoutcome'), $options);
        $mform->setDefault('gradetype', GRADE_TYPE_VALUE);
        
		// Counter
        $mform->addElement('advcheckbox', 'export_counter', get_string('exportcounter', 'checkoutcome'));
        $mform->setDefault('export_counter', 0);
		
        // Feedback
        $mform->addElement('advcheckbox', 'export_feedback', get_string('exportfeedback', 'grades'));
        $mform->setDefault('export_feedback', 0);
        
		// Preview rows
        $options = array('10'=>10, '20'=>20, '100'=>100, '1000'=>1000, '100000'=>100000);
        $mform->addElement('select', 'previewrows', get_string('previewrows', 'grades'), $options);

        if (!empty($features['updategradesonly'])) {
            $mform->addElement('advcheckbox', 'updatedgradesonly', get_string('updatedgradesonly', 'grades'));
        }
        
        if (!empty($features['includeseparator'])) {
            $radio = array();
            $radio[] = $mform->createElement('radio', 'separator', null, get_string('septab', 'grades'), 'tab');
            $radio[] = $mform->createElement('radio', 'separator', null, get_string('sepcomma', 'grades'), 'comma');
			$radio[] = $mform->createElement('radio', 'separator', null, get_string('sepsemicolon', 'checkoutcome'), 'semicolon');
            $mform->addGroup($radio, 'separator', get_string('separator', 'grades'), ' ', false);
            $mform->setDefault('separator', 'comma');
        }

        if (!empty($CFG->gradepublishing) and !empty($features['publishing'])) {
            $mform->addElement('header', 'publishing', get_string('publishing', 'grades'));
            $options = array(get_string('nopublish', 'grades'), get_string('createnewkey', 'userkey'));
            $keys = $DB->get_records_select('user_private_key', "script='grade/export' AND instance=? AND userid=?",
                            array($COURSE->id, $USER->id));
            if ($keys) {
                foreach ($keys as $key) {
                    $options[$key->value] = $key->value; // TODO: add more details - ip restriction, valid until ??
                }
            }
            $mform->addElement('select', 'key', get_string('userkey', 'userkey'), $options);
            $mform->addHelpButton('key', 'userkey', 'userkey');
            $mform->addElement('static', 'keymanagerlink', get_string('keymanager', 'userkey'),
                    '<a href="'.$CFG->wwwroot.'/grade/export/keymanager.php?id='.$COURSE->id.'">'.get_string('keymanager', 'userkey').'</a>');

            $mform->addElement('text', 'iprestriction', get_string('keyiprestriction', 'userkey'), array('size'=>80));
            $mform->addHelpButton('iprestriction', 'keyiprestriction', 'userkey');
            $mform->setDefault('iprestriction', getremoteaddr()); // own IP - just in case somebody does not know what user key is

            $mform->addElement('date_time_selector', 'validuntil', get_string('keyvaliduntil', 'userkey'), array('optional'=>true));
            $mform->addHelpButton('validuntil', 'keyvaliduntil', 'userkey');
            $mform->setDefault('validuntil', time()+3600*24*7); // only 1 week default duration - just in case somebody does not know what user key is

            $mform->disabledIf('iprestriction', 'key', 'noteq', 1);
            $mform->disabledIf('validuntil', 'key', 'noteq', 1);
        }

        $mform->addElement('header', 'gradeitems', get_string('gradeitemsinc', 'grades'));

        // Getting the list of outcomes for this course
        $outcomes = $DB->get_records('grade_outcomes',array('courseid' => $COURSE->id),'shortname');
        
        // Getting the corresponding grade items
        $grade_items = array();
        foreach ($outcomes as $outcome) {
        	$gitem = $DB->get_record('grade_items',array('courseid' => $COURSE->id,'outcomeid' => $outcome->id,'itemmodule' => $features['itemmodule'], 'iteminstance' => $features['iteminstance']));
        	if ($gitem) {
        		$grade_items[] = $gitem;
        	}        	
        }

       if (!empty($grade_items)) {
            $needs_multiselect = false;
			if ($CFG->version < 2011120100) {
			    $contextcourse = get_context_instance(CONTEXT_COURSE, $COURSE->id);
			}
			else {
			    $contextcourse = context_course::instance($COURSE->id);
			}
            $canviewhidden = has_capability('moodle/grade:viewhidden', $contextcourse);

            foreach ($grade_items as $grade_item) {
                // Is the grade_item hidden? If so, can the user see hidden grade_items?
                if ($grade_item->hidden && !$canviewhidden) {
                    continue;
                }

                if (!empty($features['idnumberrequired']) and empty($grade_item->idnumber)) {
                    $mform->addElement('advcheckbox', 'itemids['.$grade_item->id.']', $grade_item->itemname, get_string('noidnumber', 'grades'));
                    $mform->hardFreeze('itemids['.$grade_item->id.']');
                } else {
                    $mform->addElement('advcheckbox', 'itemids['.$grade_item->id.']', $grade_item->itemname, null, array('group' => 1));
                    $mform->setDefault('itemids['.$grade_item->id.']', 1);
                    $needs_multiselect = true;
                }
            }

            if ($needs_multiselect) {
                $this->add_checkbox_controller(1, null, null, 1); // 1st argument is group name, 2nd is link text, 3rd is attributes and 4th is original value
            }
        }

        $mform->addElement('hidden', 'id', $features['cmid']);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(false, get_string('submit'));

    }
}

