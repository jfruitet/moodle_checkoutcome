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
 * @author 2014 Jean FRUITET <jean.fruitet@univ-nantes.fr>
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/formslib.php');//putting this is as a safety as i got a class not found error.
require_once('./CompareDates.php');

class mod_checkoutcome_category_form extends moodleform {
	function definition() {
		$mform = & $this->_form;
		$instance = $this->_customdata;
		
		// visible elements
		$mform->addElement('header', 'general', $instance['msg']);
		$mform->addHelpButton('general', 'category','checkoutcome');

		$mform->addElement('text','shortname',get_string('shortname','checkoutcome'),'size=50');
        $mform->setType('shortname', PARAM_RAW);
		$mform->addElement('text','name',get_string('category_name','checkoutcome'),'size=50');
        $mform->setType('name', PARAM_RAW);
		$mform->addElement('textarea', 'description', get_string('category_description','checkoutcome'), 'wrap="virtual" rows="6" cols="70"');
        $mform->setType('description', PARAM_RAW);

		$mform->addRule('shortname', null, 'required', null, 'client');
		$mform->addRule('shortname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
 		$mform->addRule('name', null, 'required', null, 'client');
 		$mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
 		
 		if (!empty($instance['category'])){
				$mform->setDefault('shortname', stripslashes($instance['category']->shortname));
 				$mform->setDefault('name', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['category']->name)));
 				if (!empty($instance['category']->description)) {
 					$mform->setDefault('description', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['category']->description)));
 				}
 		}
 			
 		// hidden params
 		$mform->addElement('hidden', 'checkoutcome', $instance['checkoutcome']);
 		$mform->setType('checkoutcome', PARAM_INT);


 		$mform->addElement('hidden', 'categoryid');
 		$mform->setType('categoryid', PARAM_INT);
 		if (!empty($instance['category']) && !empty($instance['category']->id)){
 			$mform->setDefault('categoryid', $instance['category']->id);
 		}
 		else{
 			$mform->setDefault('categoryid',0);
 		}

 		$mform->addElement('hidden', 'contextid', $instance['contextid']);
 		$mform->setType('contextid', PARAM_INT);

 		// buttons
 		$this->add_action_buttons(true, get_string('savechanges', 'admin'));
	}
}

class mod_checkoutcome_display_form extends moodleform {
	function definition() {
		$mform = & $this->_form;
		$instance = $this->_customdata;
		
		// visible elements
		$mform->addElement('header', 'general', $instance['msg']);
		$mform->addHelpButton('general', 'display','checkoutcome');

		$mform->addElement('text','name',get_string('display_name','checkoutcome'),'size=50');
        $mform->setType('name', PARAM_RAW);
		$mform->addElement('textarea', 'description', get_string('display_description','checkoutcome'), 'wrap="virtual" rows="6" cols="70"');
        $mform->setType('description', PARAM_RAW);
		$mform->addRule('name', null, 'required', null, 'client');
		$mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
			
//		$mform->addElement('static', null, get_string('color_code','checkoutcome'));
//		$mform->addElement('html','<div id="container" class="colorpicker"></div>');
		$mform->addElement('text','colorcode',get_string('color_code','checkoutcome'),'size=6');
        $mform->setType('colorcode', PARAM_INT);
		//$mform->addRule('colorcode', null, 'required', null, 'client');
		//$mform->addRule('colorcode', get_string('maximumchars', '', 6), 'maxlength', 6, 'client');
		
		$mform->addElement('checkbox','iswhitefont',get_string('is_white_font','checkoutcome'));
				
		if (!empty($instance['display'])){
			$mform->setDefault('name', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['display']->name)));
			$mform->setDefault('colorcode', $instance['display']->color);
			if ($instance['display']->iswhitefont) {
				$mform->setDefault('iswhitefont', 'checked');
			}
			if (!empty($instance['display']->description)) {
				$mform->setDefault('description', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['display']->description)));
			}
		}
		
		//$mform->addRule('colorcode', get_string('minimumchars', '', 6), 'minlength', 6, 'client');
		
		// hidden params
		$mform->addElement('hidden', 'checkoutcome', $instance['checkoutcome']);
		$mform->setType('checkoutcome', PARAM_INT);

		$mform->addElement('hidden', 'displayid');
		$mform->setType('displayid', PARAM_INT);
		if (!empty($instance['display']) && !empty($instance['display']->id)){
			$mform->setDefault('displayid', $instance['display']->id);
		}
		else{
			$mform->setDefault('displayid',0);
		}

		$mform->addElement('hidden', 'contextid', $instance['contextid']);
		$mform->setType('contextid', PARAM_INT);

		// buttons
		$this->add_action_buttons(true, get_string('savechanges', 'admin'));
	}
}

class mod_checkoutcome_period_form extends moodleform {
	function definition() {
		$mform = & $this->_form;
		$instance = $this->_customdata;

		// visible elements
		$mform->addElement('header', 'general', $instance['msg']);
		$mform->addHelpButton('general', 'period','checkoutcome');

		$mform->addElement('text','shortname',get_string('shortname','checkoutcome'),'size=50');
        $mform->setType('shortname', PARAM_RAW);
		$mform->addElement('text','name',get_string('period_name','checkoutcome'),'size=50');
        $mform->setType('name', PARAM_RAW);
		$mform->addElement('textarea', 'description', get_string('period_description','checkoutcome'), 'wrap="virtual" rows="6" cols="70"');
        $mform->setType('description', PARAM_RAW);
		$mform->addRule('shortname', null, 'required', null, 'client');
		$mform->addRule('shortname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
		$mform->addRule('name', null, 'required', null, 'client');
		$mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
		
		$mform->addElement('checkbox','dateyesno',get_string('dateyesno','checkoutcome'));
		
		$mform->addElement('date_selector', 'startdate', get_string('start_date', 'checkoutcome'), array('optional'=>false));
		$mform->addElement('date_selector', 'enddate', get_string('end_date', 'checkoutcome'), array('optional'=>false));
		
		//$mform->addRule('startdate', null, 'required', null, 'client');
		//$mform->addRule('enddate', null, 'required', null, 'client');
		// custom rule to compare the two dates
		//$mform->addRule(array('startdate', 'enddate') , get_string('startdate_inf_enddate', 'checkoutcome'), 'html_quickform_rule_compare_dates', 'lt');
			
		
		if (!empty($instance['period'])){
			$mform->setDefault('shortname', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['period']->shortname)));
			$mform->setDefault('name', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['period']->name)));
			if (!empty($instance['period']->description)) {
				$mform->setDefault('description', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['period']->description)));
			}
			if (!empty($instance['period']->dateyesno)) {
				$mform->setDefault('dateyesno', 'checked');
			}
			if (!empty($instance['period']->startdate)) {
				$mform->setDefault('startdate', $instance['period']->startdate);
			}
			if (!empty($instance['period']->enddate)) {
				$mform->setDefault('enddate', $instance['period']->enddate);
			}
		}
		
		// hidden params
		$mform->addElement('hidden', 'checkoutcome', $instance['checkoutcome']);
		$mform->setType('checkoutcome', PARAM_INT);

		$mform->addElement('hidden', 'periodid');
		$mform->setType('periodid', PARAM_INT);
		if (!empty($instance['period']) && !empty($instance['period']->id)){
			$mform->setDefault('periodid', $instance['period']->id);
		}
		else{
			$mform->setDefault('periodid',0);
		}

		$mform->addElement('hidden', 'contextid', $instance['contextid']);
		$mform->setType('contextid', PARAM_INT);

		// buttons
		$this->add_action_buttons(true, get_string('savechanges', 'admin'));
	}	
	
}

class mod_checkoutcome_goal_form extends moodleform {
	function definition() {
		$mform = & $this->_form;
		$instance = $this->_customdata;
		
		$mform->addElement('header', 'general', $instance['msg']);
		//$mform->addHelpButton('general', 'period','checkoutcome');

		$mform->addElement('editor','goal',get_string('goal','checkoutcome'));
		
		$mform->addElement('editor','appraisal',get_string('appraisal','checkoutcome'));
		
		if (!empty($instance['goal'])){
			if (!empty($instance['goal']->goal)) {
				$mform->setDefault('goal', array('text' => stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['goal']->goal))));
			}
			if (!empty($instance['goal']->appraisal)) {
				$mform->setDefault('appraisal', array('text' => stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['goal']->appraisal))));
			}
		}
		
		// hidden params
		$mform->addElement('hidden', 'checkoutcome', $instance['checkoutcome']);
		$mform->setType('checkoutcome', PARAM_INT);

		$mform->addElement('hidden', 'periodid');
		$mform->setType('periodid', PARAM_INT);
		if (!empty($instance['periodid'])){
			$mform->setDefault('periodid', $instance['periodid']);
		}
		
		$mform->addElement('hidden', 'goalid');
		$mform->setType('goalid', PARAM_INT);
		if (!empty($instance['goal']) && !empty($instance['goal']->id)){
			$mform->setDefault('goalid', $instance['goal']->id);
		}
		
		$mform->addElement('hidden', 'contextid', $instance['contextid']);
		$mform->setType('contextid', PARAM_INT);
		
		$mform->addElement('hidden', 'studentid', $instance['studentid']);
		$mform->setType('studentid', PARAM_INT);
		if (!empty($instance['studentid'])){
			$mform->setDefault('studentid', $instance['studentid']);
		}
		
		// buttons
		$this->add_action_buttons(true, get_string('savechanges', 'admin'));
	}

}

class mod_checkoutcome_studentdescription_form extends moodleform {
	function definition() {
		$mform = & $this->_form;
		$instance = $this->_customdata;

		$mform->addElement('header', 'general', $instance['msg']);
		//$mform->addHelpButton('general', 'period','checkoutcome');

		$mform->addElement('editor','studentdescription', null, null, array(
                        'trusttext' => true));

		
		if (!empty($instance['goal'])){
			if (!empty($instance['goal']->studentsdescription)) {
				$mform->setDefault('studentdescription', array('text' => stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['goal']->studentsdescription))));
			}			
		}

		// hidden params
		$mform->addElement('hidden', 'checkoutcome', $instance['checkoutcome']);
		$mform->setType('checkoutcome', PARAM_INT);

		$mform->addElement('hidden', 'periodid');
		$mform->setType('periodid', PARAM_INT);
		if (!empty($instance['periodid'])){
			$mform->setDefault('periodid', $instance['periodid']);
		}

		$mform->addElement('hidden', 'goalid');
		$mform->setType('goalid', PARAM_INT);
		if (!empty($instance['goal']) && !empty($instance['goal']->id)){
			$mform->setDefault('goalid', $instance['goal']->id);
		}

		$mform->addElement('hidden', 'contextid', $instance['contextid']);
		$mform->setType('contextid', PARAM_INT);

		$mform->addElement('hidden', 'userid', $instance['userid']);
		$mform->setType('userid', PARAM_INT);
		if (!empty($instance['userid'])){
			$mform->setDefault('userid', $instance['userid']);
		}

		// buttons
		$this->add_action_buttons(true, get_string('savechanges', 'admin'));
	}

}

class mod_checkoutcome_document_form extends moodleform {
	function definition() {
		global $CFG;
		
		$mform = & $this->_form;
		$instance = $this->_customdata;

		// visible elements
		$mform->addElement('header', 'general', $instance['msg']);
		$mform->addHelpButton('general', 'document','checkoutcome');

		$mform->addElement('text','title',get_string('document_title','checkoutcome'),'size=50');
        $mform->setType('title', PARAM_RAW);
		//$mform->addRule('title', null, 'required', null, 'client');
		$mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

		$mform->addElement('textarea', 'description', get_string('document_description','checkoutcome'), 'wrap="virtual" rows="6" cols="70"');
        $mform->setType('description', PARAM_RAW);

		if (!empty($instance['document'])){
			$mform->setDefault('title', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['document']->title)));
			if (!empty($instance['document']->description)) {
				$mform->setDefault('description', stripslashes(str_replace(array('&quot;','&rsquo;'),array('"',"'"),$instance['document']->description)));
			}			
			if (!empty($instance['document']->url)) {
				$mform->addElement('static', 'url_old', get_string('document_url_old','checkoutcome'));
				$mform->setDefault('url_old', $instance['document']->url);
			}
		}
		
		$mform->addElement('filepicker', 'document_file', get_string('uploadafile'), null, $instance['options']);
		
		if (empty($instance['document'])){
			$mform->addRule('document_file', null, 'required', null, 'client');
		}		
		
		// hidden params
		$mform->addElement('hidden', 'checkoutcome', $instance['checkoutcome']);
		$mform->setType('checkoutcome', PARAM_INT);
		
		$mform->addElement('hidden', 'itemid', $instance['gradeid']);
		$mform->setType('itemid', PARAM_INT);
		
		$mform->addElement('hidden', 'chitemid', $instance['chitemid']);
		$mform->setType('chitemid', PARAM_INT);
		
		$mform->addElement('hidden', 'selected_periodid', $instance['periodid']);
		$mform->setType('selected_periodid', PARAM_INT);
		
		$mform->addElement('hidden', 'documentid');
 		$mform->setType('documentid', PARAM_INT);
 		if (!empty($instance['document']) && !empty($instance['document']->id)){
 			$mform->setDefault('documentid', $instance['document']->id);
 		}

 		$mform->addElement('hidden', 'contextid', $instance['contextid']);
 		$mform->setType('contextid', PARAM_INT);
 		
 		$mform->addElement('hidden', 'filearea', $instance['filearea']);
 		$mform->setType('filearea', PARAM_ALPHA);
		
		
		// buttons
		$this->add_action_buttons(true, get_string('savechanges', 'admin'));
	}
}

class mod_checkoutcome_editoutcome_form extends moodleform {
	function definition() {
		global $DB;
		
		$mform = & $this->_form;
		$instance = $this->_customdata;

		// visible elements
		$mform->addElement('header', 'general', $instance['msg']);
		$mform->addHelpButton('general', 'outcome','checkoutcome');

		// Outcome name
		$mform->addElement('static','name',get_string('outcome_name','checkoutcome'));
		if (!empty($instance['gradeitem'])) {
			$mform->setDefault('name', $instance['gradeitem']->itemname);
		}

		// Teacher scale (student scale is commented)
		if (!empty($instance['courseid'])) {
			if ($scales = $DB->get_records('scale', array('courseid' => $instance['courseid']))) {				
				
				$selected_teacherscaleid = NULL;
				if (!empty($instance['gradeitem'])) {
					$selected_teacherscaleid = $instance['gradeitem']->scaleid;
				}
// 				$selected_studentscaleid =NULL;
// 				if (!empty($instance['checkoutcomeitem'])) {
// 					$selected_studentscaleid = $instance['checkoutcomeitem']->scaleid;
// 				}				
				$scales_names = array();
				//$i = 0;
				$default_teacherscale_index = NULL;
				$default_teacherscale_name = NULL;
// 				$default_studentscale_index = NULL;
// 				$default_studentscale_name = NULL;
				foreach ($scales as $scale) {
					$scales_names[$scale->id] = $scale->name;
					if($selected_teacherscaleid && $selected_teacherscaleid == $scale->id) {
						$default_teacherscale_index = $scale->id;
						$default_teacherscale_name = $scale->name;
					}
// 					if($selected_studentscaleid && $selected_studentscaleid == $scale->id) {
// 						$default_studentscale_index = $scale->id;
// 						$default_studentscale_name = $scale->name;
// 					}
					//$i++;
				}
				
				// Teacher scale
				$mform->addElement('static', 'teacherscale_name', get_string('teacher_scale', 'checkoutcome'), $default_teacherscale_name);
				//Hidden parameters to submit
				$mform->addElement('hidden', 'teacherscale', $default_teacherscale_index);
				$mform->setType('teacherscale', PARAM_INT);
				
// 				//Student scale
// 				if (!$instance['inuse'] && $instance['inuse'] == 0) {					
// 					$mform->addElement('select', 'studentscale', get_string('student_scale', 'checkoutcome'), $scales_names);
// 					//Default values					
// 					$mform->setDefault('studentscale', $default_studentscale_index);					
// 				} else {
// 					$mform->addElement('static', 'studentscale_name', get_string('student_scale', 'checkoutcome'), $default_studentscale_name);
// 					//Hidden parameters to submit
// 					$mform->addElement('hidden', 'studentscale', $default_studentscale_index);
// 					$mform->setType('studentscale', PARAM_INT);
// 				}
			}
		}
		
		
		// Type, Category
		
		// Category
		if (!empty($instance['checkoutcome'])) {
		
			$category_names = array();
			$category_names[0] = get_string('NA','checkoutcome');
			$default_category_index = 0;
			if ($categories = $DB->get_records('checkoutcome_category',array('checkoutcome' => $instance['checkoutcome']))) {
				foreach ($categories as $categ) {
					$category_names[$categ->id] = $categ->name;
					if (!empty($instance['checkoutcomeitem']) && $instance['checkoutcomeitem']->category && $categ->id == $instance['checkoutcomeitem']->category) {
						$default_category_index = $categ->id;
					}
				}
			}
			$mform->addElement('select', 'category', get_string('category_choice', 'checkoutcome'), $category_names);
			$mform->setDefault('category', $default_category_index);
		}	
		
		// Display
		if (!empty($instance['checkoutcome'])) {		
			$display_names = array();
			$display_names[0] = get_string('NA','checkoutcome');
			$default_display_index = 0;
			if ($displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $instance['checkoutcome']))) {
				foreach ($displays as $disp) {
					$display_names[$disp->id] = $disp->name;
					if (!empty($instance['checkoutcomeitem']) && $instance['checkoutcomeitem']->display && $disp->id == $instance['checkoutcomeitem']->display) {
						$default_display_index = $disp->id;
					}
				}
			}
			$mform->addElement('select', 'display', get_string('display_choice', 'checkoutcome'), $display_names);
			$mform->setDefault('display', $default_display_index);
		}			

		// hidden params
		$mform->addElement('hidden', 'checkoutcome', $instance['checkoutcome']);
		$mform->setType('checkoutcome', PARAM_INT);

		// gradeitem to be updated
		$mform->addElement('hidden', 'gradeitemid');
		$mform->setType('gradeitemid', PARAM_INT);
		if (!empty($instance['gradeitem']) && !empty($instance['gradeitem']->id)){
			$mform->setDefault('gradeitemid', $instance['gradeitem']->id);
		}
	
		// checkoutcomeitem to be updated
		$mform->addElement('hidden', 'checkoutcomeitemid');
		$mform->setType('checkoutcomeitemid', PARAM_INT);
		if (!empty($instance['checkoutcomeitem']) && !empty($instance['checkoutcomeitem']->id)){
			$mform->setDefault('checkoutcomeitemid', $instance['checkoutcomeitem']->id);
		}

		$mform->addElement('hidden', 'contextid', $instance['contextid']);
		$mform->setType('contextid', PARAM_INT);

		// buttons
		$this->add_action_buttons(true, get_string('savechanges', 'admin'));
	}
}

// ############################ MOODLE 2.0 FILE API #########################

//------------------
function checkoutcome_edit_category($mform, $checkoutcomeid){

	global $CFG, $USER, $DB, $OUTPUT;

	$viewurl=new moodle_url('/mod/checkoutcome/list_cat.php', array('checkoutcome'=>$checkoutcomeid));

	if ($formdata = $mform->get_data()) {
		if (empty($formdata->categoryid)){
			$category = new object();
			$category->checkoutcome=$checkoutcomeid;
			$category->shortname=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->shortname));
			$category->name=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->name));
			$category->description=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->description));
			$category->timecreated=$category->timemodified=time();
			// Insert new Category
			if ($catid = $DB->insert_record("checkoutcome_category", $category)){
				$category->id=$catid;
			}
			else{
				return NULL;
			}
		}
		else{
			$category = new object();			
			$category->id=$formdata->categoryid;
			$category->checkoutcome=$checkoutcomeid;
			$category->shortname=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->shortname));
			$category->name=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->name));
			$category->description=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->description));
			$category->timemodified=time();
			// Update Category
			$DB->update_record("checkoutcome_category", $category);
		}
	}
	redirect($viewurl);
}

function checkoutcome_edit_display($mform, $checkoutcomeid){
	global $CFG, $USER, $DB, $OUTPUT;

	$viewurl=new moodle_url('/mod/checkoutcome/list_disp.php', array('checkoutcome'=>$checkoutcomeid));

	if ($formdata = $mform->get_data()) {
		if (empty($formdata->displayid)){
			$disp = new object();
			$disp->checkoutcome=$checkoutcomeid;
			$disp->name=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->name));
			$disp->description=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->description));
			if ($formdata->colorcode == null) {
				$color = 'FFFFFF';
			} else {
				$color = $formdata->colorcode;
			}
			if (strrpos($color,",")) {
				$color = rgbtoHex($color);
			}			
			$disp->color=$color;
			if (!empty($formdata->iswhitefont)) {
				$disp->iswhitefont = 1;
			} else {
				$disp->iswhitefont = 0;
			}			
			$disp->timecreated=$disp->timemodified=time();
			// Insert new Display
			if ($dispid = $DB->insert_record("checkoutcome_display", $disp)){
				$disp->id=$dispid;
			}
			else{
				return NULL;
			}
		}
		else{
			$disp = new object();
			$disp->id=$formdata->displayid;
			$disp->checkoutcome=$checkoutcomeid;
			$disp->name=$formdata->name;
			$disp->description=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->description));
			$color = $formdata->colorcode;
			if (strrpos($color,",")) {
				$color = rgbtoHex($color);
			}			
			$disp->color=$color;
			if (!empty($formdata->iswhitefont)) {
				$disp->iswhitefont = 1;
			} else {
				$disp->iswhitefont = 0;
			}
			$disp->timemodified=time();
			// Update Display
			$DB->update_record("checkoutcome_display", $disp);
		}
	}
	redirect($viewurl);
}

function rgbtoHex($color) {
	$hex_RGB = '';
	$color = explode(",",$color);
	foreach($color as $value){
		$hex_value = dechex($value);
		if(strlen($hex_value)<2){
			$hex_value="0".$hex_value;
		}
		$hex_RGB.=$hex_value;
	}		
	return $hex_RGB;
}

function checkoutcome_edit_period($mform, $checkoutcomeid){
	global $DB;

	$viewurl=new moodle_url('/mod/checkoutcome/list_period.php', array('checkoutcome'=>$checkoutcomeid));

	if ($formdata = $mform->get_data()) {
		if (empty($formdata->periodid)){
			$period = new object();
			$period->checkoutcome=$checkoutcomeid;
			$period->shortname=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->shortname));
			$period->name=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->name));
			$period->description=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->description));
			if (!empty($formdata->dateyesno))
			{
				$period->startdate=$formdata->startdate;
				$period->enddate=$formdata->enddate;
			}
			else
			{
				$period->startdate=null;
				$period->enddate=null;
			}
			$period->timecreated=$period->timemodified=time();
			// Insert new Period
			if ($periodid = $DB->insert_record("checkoutcome_periods", $period)){
				$period->id=$periodid;
			}
			else{
				return NULL;
			}
		}
		else{
			$period = new object();
			$period->id=$formdata->periodid;
			$period->checkoutcome=$checkoutcomeid;
			$period->shortname=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->shortname));
			$period->name=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->name));
			$period->description=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->description));
			if (!empty($formdata->dateyesno))
			{
				$period->startdate=$formdata->startdate;
				$period->enddate=$formdata->enddate;
			}
			else
			{
				$period->startdate=null;
				$period->enddate=null;
			}
			$period->timemodified=time();
			// Update Period
			$DB->update_record("checkoutcome_periods", $period);
		}
	}
	redirect($viewurl);
}

function checkoutcome_edit_goal($mform, $checkoutcomeid, $groupid){
	global $DB,$USER;

	$viewurl=new moodle_url('/mod/checkoutcome/periodgoals.php', array('checkoutcome'=>$checkoutcomeid, 'group' => $groupid));

	if ($formdata = $mform->get_data()) {		
		if (empty($formdata->goalid)){
			$goal = new object();
			$goal->period=$formdata->periodid;
			$goal->userid=$formdata->studentid;
			$goal->usermodified=$USER->id;
			$goal->goal=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->goal['text']));
			$goal->appraisal=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->appraisal['text']));
			$goal->timecreated=$goal->timemodified=time();
			// Insert new Goal
			if ($goalid = $DB->insert_record("checkoutcome_period_goals", $goal)){
				$goal->id=$goalid;
			}
			else{
				return NULL;
			}
		}
		else{
			$goal = new object();
			$goal->id=$formdata->goalid;
			$goal->period=$formdata->periodid;
			$goal->userid=$formdata->studentid;
			$goal->usermodified=$USER->id;
			$goal->goal=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->goal['text']));
			$goal->appraisal=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->appraisal['text']));
			$goal->timemodified=time();
			// Update Period
			$DB->update_record("checkoutcome_period_goals", $goal);
		}
		// On ajoute le studentid, la periodid et le goal id a l'url
		$viewurl->params(array('studentid' => $formdata->studentid, 'selected_periodid' => $formdata->periodid, 'goalid' => $goal->id));		
	}
	redirect($viewurl);
}

function checkoutcome_edit_studentdescription($mform, $checkoutcomeid, $periodid){
	global $DB;

	$viewurl=new moodle_url('/mod/checkoutcome/view.php', array('checkoutcome'=>$checkoutcomeid, 'selected_periodid' => $periodid));

	if ($formdata = $mform->get_data()) {
		if (empty($formdata->goalid)){
			$goal = new object();
			$goal->period=$formdata->periodid;
			$goal->userid=$goal->usermodified=$formdata->userid;			
			$goal->studentsdescription=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->studentdescription['text']));
			$goal->timecreated=$goal->timemodified=time();
			// Insert new Goal
			if ($goalid = $DB->insert_record("checkoutcome_period_goals", $goal)){
				$goal->id=$goalid;
			}
			else{
				return NULL;
			}
		}
		else{
			$goal = new object();
			$goal->id=$formdata->goalid;
			$goal->period=$formdata->periodid;
			$goal->userid=$goal->usermodified=$formdata->userid;
			$goal->studentsdescription=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->studentdescription['text']));
			$goal->timemodified=time();
			// Update Period
			$DB->update_record("checkoutcome_period_goals", $goal);
		}
	}
	redirect($viewurl);
}

function checkoutcome_edit_document($mform, $checkoutcomeid, $ch_itemid, $periodid) {
	global $DB;
	
	$viewurl=new moodle_url('/mod/checkoutcome/view.php', array('checkoutcome'=>$checkoutcomeid, 'selected_periodid' => $periodid));
	
	if ($ch_itemid) {
		$viewurl->set_anchor('ch_item_'.$ch_itemid); // set anchor to the url
	}
	
	$docid = null;	
	
	if ($formdata = $mform->get_data()) {
		
		// get the uploaded file name if it exists
		$newfilename = $mform->get_new_filename('document_file');		
		
		$doc = new stdClass();
		$doc->gradeid=$formdata->itemid;
		$doc->description=addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->description));
		if(empty($formdata->title)) {
			if($newfilename) {
				$doc->title = $newfilename;
			}
		} else {
			$doc->title = addslashes(str_replace(array('"',"'"),array('&quot;','&rsquo;'),$formdata->title));
		}		
			
		if (empty($formdata->documentid)){
			$doc->timecreated=$doc->timemodified=time();
			// Insert new document
			if ($docid = $DB->insert_record("checkoutcome_document", $doc)){
					$doc->id=$docid;
			} else {
				return NULL;
			}
		} else {
			$doc->id=$docid=$formdata->documentid;
			$doc->timemodified=time();
			// Update Description
			$DB->update_record("checkoutcome_document", $doc);
		}
		// Verifier si un fichier est depose
		if ($newfilename) {
			// get the id for url
			if ($docid) {
				$file = $mform->save_stored_file('document_file', $formdata->contextid,
						'mod_checkoutcome', $formdata->filearea, $docid, '/', $newfilename);
				// file address calculation
				$fullpath = "/$formdata->contextid/mod_checkoutcome/$formdata->filearea/$docid/$newfilename";
				// Update url
				$DB->set_field('checkoutcome_document', 'url', $fullpath, array('id' => "$docid"));
				//$DB->set_field('checkoutcome_document', 'fileid', $file->file_record->id, array('id' => $docid));				
			}
		}		
	}
	redirect($viewurl);
}

function checkoutcome_edit_outcome($mform, $checkoutcomeid){
	// file form managing
	// table checklist_document update
	global $CFG, $USER, $DB, $OUTPUT;

	$viewurl=new moodle_url('/mod/checkoutcome/edit.php', array('checkoutcome'=>$checkoutcomeid));

	if ($formdata = $mform->get_data()) {
		if (!empty($formdata->gradeitemid)){
			$gradeitem = $DB->get_record('grade_items', array('id'=>$formdata->gradeitemid));
			$gradeitem->scaleid = $formdata->teacherscale;
			$gradeitem->timemodified=time();
			// Update grade item
			$DB->update_record("grade_items", $gradeitem);
		}
		if (!empty($formdata->checkoutcomeitemid)){
			$ch_item = $DB->get_record('checkoutcome_item', array('id'=>$formdata->checkoutcomeitemid));
			$ch_item->scaleid = $formdata->teacherscale;					
			$ch_item->display = $formdata->display;
			$ch_item->category = $formdata->category;
			$ch_item->timemodified=time();
			// Update Checkoutcome item
			$DB->update_record("checkoutcome_item", $ch_item);
		}	
	}
	redirect($viewurl);
}
