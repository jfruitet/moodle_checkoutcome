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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require './file_api.php';
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once ($CFG->dirroot.'/grade/export/lib.php');
require_once './export/txt/checkoutcome_grade_export_txt.php';
require_once './export/checkoutcome_grade_export_form.php';
require_once($CFG->dirroot.'/lib/pdflib.php');
require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->libdir . '/portfolio/caller.php');
require_once ($CFG->libdir . '/grouplib.php');
require_once './pdf/checkoutcome_pdf.php';
require_once './portofolio/locallib_portofolio.php';

/**
 * Main class of the checkoutcome module
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkoutcome_class {

    var $cm;
    var $course;
    var $checkoutcome;
    var $strcheckoutcomes;
    var $strcheckoutcome;
    var $context;
    var $userid;
    var $items;
    var $categories;
    var $displays;
	var $groups;
	var $groupings;
	var $groupingid;
  	var $currentgroup;
  	var $groupmode;
  	var $groupmembersonly;
    var $studentid;
    var $category_items;   
    var $periods;
    var $selected_period;
    var $isPDFexport;

    
    /**
     * Default constructor
     * @param Integer $cmid
     * @param Integer $userid
     * @param Checkoutcome $checkoutcome
     * @param Course_module $cm
     * @param Course $course
     * @param Integer $studentid
     */
    function checkoutcome_class($cmid='staticonly', $userid=0, $checkoutcome=NULL, $cm=NULL, $course=NULL, $studentid=0, $group=0, $selected_periodid=0) {
        global $COURSE, $DB, $CFG, $USER;

        if ($cmid == 'staticonly') {
            //use static functions only!
            return;
        }
        $this->userid = $userid;
        
       	$this->studentid = $studentid;
       
        if ($cm) {
            $this->cm = $cm;
        } else if (! $this->cm = get_coursemodule_from_id('checkoutcome', $cmid)) {
            print_error(get_string('error_cmid', 'checkoutcome')); // 'Course Module ID was incorrect'
        }

    	if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }

        if ($course) {
            $this->course = $course;
        } else if ($this->cm->course == $COURSE->id) {
            $this->course = $COURSE;
        } else if (! $this->course = $DB->get_record('course', array('id' => $this->cm->course) )) {
            print_error(get_string('error_course', 'checkoutcome')); // 'Course is misconfigured'
        }

        if ($checkoutcome) {
            $this->checkoutcome = $checkoutcome;
        } else if (! $this->checkoutcome = $DB->get_record('checkoutcome', array('id' => $this->cm->instance) )) {
            print_error(get_string('error_checkoutcome_id', 'checkoutcome')); // 'checklist ID was incorrect'
        }
        
        if (isset($CFG->enablegroupmembersonly) && $CFG->enablegroupmembersonly) {
        	$this->groupings = groups_get_user_groups($this->course->id, $USER->id);
            $this->groupingid = $this->cm->groupingid;
            $this->groupmembersonly = $this->cm->groupmembersonly;
        } else {
            $this->groupings = false;
            $this->groupingid = 0;
            $this->groupmembersonly = false;          
        }     
        
        // get all groups (of the grouping if any)
        if ($this->groupmembersonly && !$this->canviewallgroups()) {
        	$this->groups = groups_get_all_groups($this->course->id, $USER->id, $this->groupingid);
        } else {
        	$this->groups = groups_get_all_groups($this->course->id, NULL, $this->groupingid);
        }
        
        
        // set current group 
        if (array_key_exists($group, $this->groups)) {
        	$this->currentgroup = $group;
        } else {
        	$this->currentgroup = 0;
        }
        
		// set group mode
        $this->groupmode = groups_get_activity_groupmode($cm);

        $this->strcheckoutcome = get_string('modulename', 'checkoutcome');
        $this->strcheckoutcomes = get_string('modulenameplural', 'checkoutcome');
        $this->pagetitle = strip_tags($this->course->shortname.': '.$this->strcheckoutcome.': '.format_string($this->checkoutcome->name,true));
        
        if ($selected_periodid) {
        	$this->selected_period = $DB->get_record('checkoutcome_periods', array('id' => $selected_periodid));
        }
        
        $this->isPDFexport = false;
        
		$this->get_items();      
    }
    
    /**
     * Fill in the array items ($this->items) for this instance of checkoutcome_class
     * $this->items is an array of subarrays with the following description :
     * $item[0] : instance of mdl_grade_items
     * $item[1] : instance of mdl_ckeckoutcome_items
     * $item[2] : instance of mdl_checkoutcome_selfgrading if it exists
     * $item[3] : instance of mdl_grade_grades if it exists
     * $item[4] : instance of the corresponding mdl_grade_outcomes
     * $this->items loads all usefull informations to manipulate outcomes, 
     * grades and so on in this module
     */
    function get_items() {
    	global $DB;
    
    	$this->items = NULL;
    	
    	// Set student id
    	if ($this->studentid) {
    		$studentid = $this->studentid;
    	} else {
    		$studentid = $this->userid;
    	}	
    	
    	// handle the case after restore when ch_item has old grade item value
    	$this->checkAffectedGradeItems();
    	    	
    	// Getting the corresponding grade items and create new checkoutcome item if needed (if grade item 
    	// was created through module creation form or through backup/restore no checkoutcome item has been linked) 	
    	    		
     	$gitems = $DB->get_records('grade_items',array('courseid' => $this->course->id,'itemmodule' => $this->cm->modname, 'iteminstance' => $this->cm->instance));
     	foreach ($gitems as $gitem) {
    	//check that a corresponding entry exists in table checkoutcome_item, if not insert one
    	if (!$ch_item = $DB->get_record('checkoutcome_item',array('gradeitem' => $gitem->id))) { 				
    			$itemnumber = $this->getCheckoutcomeNewItemNumber();    			
    			$id = $this->insert_checkoutcome_item($gitem,$itemnumber);    			
    			$ch_item = $DB->get_record('checkoutcome_item',array('id' => $id));  			
			}
    	}
    	    	
    	// Getting periods
    	$this->periods = $DB->get_records('checkoutcome_periods',array('checkoutcome' => $this->checkoutcome->id),'shortname');
    	// create and insert default period if no other period has been created yet:
    	// this is always the case after module creation
    	if (empty($this->periods)) {
    		$default_period = new stdClass();
    		$default_period->checkoutcome = $this->checkoutcome->id;
    		$default_period->name = get_string('default_period_name','checkoutcome');
			$default_period->shortname = '0';
    		$default_period->startdate = 0;
    		$default_period->enddate = 0;
    		$default_period->timecreated=$default_period->timemodified=time();
    		//insert default period in database
    		$default_period->id = $DB->insert_record('checkoutcome_periods', $default_period);
    		// Add default period to periods array
    		$this->periods[] = $default_period;
    	}
    	 
    	// Set selected period
    	if ($this->selected_period == null) {
    		// set current period if selected period is null
    		$now = time();
    		foreach ($this->periods as $period) {
    			if ($period->startdate < $now && $period->enddate > $now) {
    				$this->selected_period = $period;
    				break;
    			}
    		}
    		// set first period in list if selected period is null
    		if ($this->selected_period == null) {
    			foreach ($this->periods as $period) {
    				$this->selected_period = $period;
    				break;
    			}    			
    		}
    	}
    	
    	// Getting categories
    	$this->categories = $DB->get_records('checkoutcome_category',array('checkoutcome' => $this->checkoutcome->id),'shortname');
    	
    	// Add category NA
    	$categNA = new stdClass();
    	$categNA->id = 0;
    	if (empty($this->categories)) {
    		$categNA->name = '';
    		$categNA->description ='';
    	} else {
    		$categNA->name = get_string('no_category_name','checkoutcome');
    		$categNA->description = get_string('no_category_desc','checkoutcome');
    	}
    	
    	// Add category NA to the first position of categories array    	
    	$this->categories[0] = $categNA;
    	
    	// fill in $this->items sorted by category, by shortname
    	foreach ($this->categories as $categ) {
    		// getting checkoutcome items of the category sorted by category, shortname
    		$sql = 'select ch.id from {checkoutcome_item} as ch,{grade_items} as g, {grade_outcomes} as go where ch.category = ? and ch.gradeitem = g.id and g.outcomeid = go.id and g.itemmodule = ? and g.iteminstance = ? order by ch.category,go.shortname';
    		
    		$ch_ids = $DB->get_records_sql($sql,array($categ->id, $this->cm->modname, $this->cm->instance));
    		
    		foreach ($ch_ids as $ch_id) {
    			// get corresponding checkoutcome item
    			$ch_item = $DB->get_record('checkoutcome_item', array('id' => $ch_id->id));    			
    			// get corresponding grade item
    			$gitem = $DB->get_record('grade_items', array('id' => $ch_item->gradeitem));
    			// get corresponding outcome
    			$outcome = $DB->get_record('grade_outcomes', array('id' => $gitem->outcomeid));
    	

    			// grade item
    			$item[0] = $gitem;
    			// checkoutcome item
    			$item[1] = $ch_item;
    			// self grades (by student himself)
    			$item[2] = null;
    			// grades (by teacher)
    			$item[3] = null;
    			// outcome
    			$item[4] = $outcome;
    			 
    			//get self grades of the period if existing
    			if ($sg_grade = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem'=>$ch_item->id, 'userid' => $studentid, 'period' => $this->selected_period->id))) {
    				$item[2] = $sg_grade;
    			}
    			 
    			//get teacher grades if existing
    			if ($t_grade = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem'=>$ch_item->id, 'userid' => $studentid, 'period' => $this->selected_period->id))) {
    				$item[3] = $t_grade;
    			}
    			
    			$this->items[$ch_item->id]= $item;
    		}
    	}
    	
    	// Getting displays
    	$this->displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $this->checkoutcome->id),'id');    	
    	    
    }   
    
    /**
     * Main method of the view screen
     */
    function view() {
    	global $OUTPUT,$DB,$CFG;
    	
    	// Url paramaters
    	$action = optional_param('action', false, PARAM_TEXT);
    	$pageContent = optional_param('pageContent', null, PARAM_CLEANHTML);    	
    	
    	// Redirect ?
    	if ($this->isGradingByStudent() || $this->isGradingByTeacher()) {
    		$currenttab = 'view';
     	} else {
     		$currenttab = 'preview';
      	}    	    	
    	
    	// Process actions
    	if ($this->canupdateown() || $this->studentid) {
    		$this->process_view_actions();
    	}

    	// Header
    	$this->view_header();	
    	    	
    	// Tabs
    	if ($this->studentid == null) {
    		$this->view_tabs($currenttab);
    	}   	
    	
    	// View list
    	$this->view_items();    	
    	
    	// Footer
    	$this->view_footer();
    	
    }
    
    /**
     * Main method of the edit screen
     */
    function edit() {
    	global $OUTPUT;
    	 
    	if (!$this->canedit()) {
    		redirect(new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id)) );
    	}
    
    	$this->view_header();
    
	   	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);    	
    
    	$this->view_tabs('setting');    	
    	$this->view_tabs_setting('edit');
    
    	$this->process_edit_actions();
    	 
    	$this->view_edit();
    
    	$this->view_footer();
    
    }
    
    /**
     * Main method of the setting screen
     */
   	function setting() {
    	global $OUTPUT;    
 
    	$this->view_header();    
 
     	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1); 
    
    	$this->view_tabs('setting');
    	$this->view_tabs_setting('edit');    
   
    	$this->view_edit();
    
    	$this->view_footer();
    
    }
    
    /**
     * Main method of the list_categories screen
     */
    function list_categories() {
    	global $OUTPUT;
    	
    	if (!$this->canedit()) {
    		redirect(new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id)) );
    	}
    	 
    	$this->view_header();    	 

     	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);  
    	 
    	$this->view_tabs('setting');
    	$this->view_tabs_setting('list_cat');
    	 
    	$this->process_list_cat_actions();
    	    	 
    	$this->view_list_cat();
    	 
    	$this->view_footer();
    	 
    }
    
    /**
     * Main method of the list_gradings screen
     */
    function list_gradings() {
    	global $OUTPUT, $CFG;
    	    
    	$this->view_header();
    
    	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);
    	
    	// print groups select menu
    	if ($this->canviewallgroups() || $this->groupmode == 2) {
    		groups_print_activity_menu($this->cm, $CFG->wwwroot . '/mod/checkoutcome/list_gradings.php?id=' . $this->cm->id.'&periodid='.$this->selected_period->id);
    	}    	
    
    	$this->view_tabs('list_gradings');
    	
    	$this->view_list_gradings();
    
    	$this->view_footer();    
    }
    
    /**
     * Main method of the report screen
     */
    function report() {
    	global $OUTPUT;
    		
    	$this->view_header();
    	
	 	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);
    
    	$this->view_tabs('report');
    	$this->view_tabs_report('summary');    
    
    	$this->view_summary();
    
    	$this->view_footer();    
    }
    
    /**
     * Main method of the summary screen
     */
    function summary() {
    	global $OUTPUT;
    
    	$this->view_header();    	
	
     	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);
    
    	$this->view_tabs('report');
    	$this->view_tabs_report('summary');    
  
    	$this->view_summary();
    
    	$this->view_footer();
    }
    
    /**
     * Main method of the export screen
     */
    function export() {
    	global $OUTPUT;
    
    	$this->view_header();    	 
   	 
     	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);
    
    	$this->view_tabs('report');
    	$this->view_tabs_report('export');
    	    
    	$this->view_export();
    
    	$this->view_footer();
    }
    
    /**
     * Main method of the list_displays screen
     */
    function list_displays() {
    	global $OUTPUT;
    	 
    	if (!$this->canedit()) {
    		redirect(new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id)) );
    	}
    
    	$this->view_header();    	
  
     	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);  
    
    	$this->view_tabs('setting');
    	$this->view_tabs_setting('list_disp');
    
    	$this->process_list_disp_actions();
    	 
    	$this->view_list_disp();
    
    	$this->view_footer();
    
    }
    
    /**
     * Main method of the list_periods screen
     */
    function list_periods() {
    	global $OUTPUT;
    
    	if (!$this->canedit()) {
    		redirect(new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id)) );
    	}
    
    	$this->view_header();
    
    	echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);
    
    	$this->view_tabs('setting');
    	$this->view_tabs_setting('list_period');
    
    	$this->process_list_period_actions();
    
    	$this->view_list_period();
    
    	$this->view_footer();
    
    }
    
    /**
     * Main method of the update_category screen
     */
    function update_category() {
    	$this->view_update_cat();   
    }
    
    /**
     * Main method of the update_display screen
     */
    function update_display() {
   		$this->view_update_disp();
    }
    
    /**
     * Main method of the update_period screen
     */
    function update_period() {
    	$this->view_update_period();
    }
    
    /**
     * Main method of the edit_outcome screen
     */
    function edit_outcome() {
    	global $OUTPUT;

    	$this->view_edit_outcome();
    
    }
    
    /**
     * Main method of the add_outcome screen
     */
    function add_outcome() {

    	$this->view_add_outcome();
    
    }
    
    /**
     * Main method of the add_document screen
     */
    function add_document() {    	
    	$this->view_add_document();    	
    }
    
    /**
     * Main method of the peeriod goals screen
     */
    function periodgoals() {
    	    	 
    	// Process actions
    	if (has_capability('moodle/course:manageactivities', $this->context)) {
    		$this->process_periodgoals_actions();
    	}   	
    	 
    	// View period goals formular
    	$this->view_periodgoals();   	
    	 
    }
    
    /**
     * Main method of the studentdescription screen
     */
    function studentdescription() {
    
       	$this->view_studentdescription();  	
    
    }
    
    /**
     * Process actions of the view screen
     */
    function process_view_actions() {
    	global $CFG, $DB;
    	$action = optional_param('action', false, PARAM_TEXT);
    	if (!$action) {
    		return;
    	}
    	 
    	switch($action) {

    		case 'updateoptions':
    			if ($CFG->version < 2011120100) {
    				$newoptions = optional_param('items', array(), PARAM_INT);
    			} else {
    				$newoptions = optional_param_array('items', array(), PARAM_INT);
    			}
    			$this->updateoptions($newoptions);
    			$this->get_items();
    			break;
    		case 'addComment':
    				$itemid = required_param('itemid', PARAM_INT);
    				$comment = required_param('comment', PARAM_TEXT);
    				$this->updatecomment($itemid, $comment);
    				$this->get_items();
    				break;
    		case 'addteacherComment':
    				$itemid = required_param('itemid', PARAM_INT);
    				$comment = required_param('teacher_comment', PARAM_TEXT);
    				$this->updateteachercomment($itemid, $comment);
    				$this->get_items();
    				break;    		
    		case 'deleteComment':
    				$sid = required_param('itemid', PARAM_INT);
    				$this->deletecomment($sid);
    				$this->get_items();
    				break;
    		case 'deleteteacherComment':
    				$sid = required_param('itemid', PARAM_INT);
    				$this->deleteteachercomment($sid);
    				$this->get_items();
    				break;
    		case 'deleteDocument':
    				$docid = required_param('documentid', PARAM_INT);
    				$DB->delete_records('checkoutcome_document', array('id' => $docid));
    				$this->get_items();
    				break;
    		case 'exportPDF':
    				$this->isPDFexport = true;
    				$this->exportPDF();
    				break;
    		case 'updateCounts':
    				if ($CFG->version < 2011120100) {
    					$newcounts = optional_param('items', array(), PARAM_INT);
    				} else {
    					$newcounts = optional_param_array('items', array(), PARAM_INT);
    				}
    				$this->update_counts($newcounts);
    				break;
    		default:
    			print_error(get_string('error_action', 'checkoutcome', s($action))); // 'Invalid action - "{a}"'
    	}
    }
    
    /**
     * Process actions of the edit screen
     */
    function process_edit_actions() {
    	global $DB,$_REQUEST, $CFG;
    	 
    	$action  = optional_param('action', 0, PARAM_ALPHA);  // action
    	$gradeitemid = optional_param('gradeitemid', 0, PARAM_INT);  // gradeitem ID
    	$checkoutcomeitemid  = optional_param('checkoutcomeitemid', 0, PARAM_INT);  // checkoutcomeitem ID
    	
    	if ($action) {
    
    		switch ($action) {
    			case 'deleteOutcome':
    				// Delete grade item
    				$DB->delete_records('grade_items', array('id'=>$gradeitemid));
    				// Delete checkoutcome item
    				$DB->delete_records('checkoutcome_item', array('id'=>$checkoutcomeitemid));
    				$this->get_items();
    				break;
    			case 'addOutcome':
    				// Getting outcome ids to add
    				$outcomeids = array();
    				foreach ($_REQUEST as $param => $value) {
   						$reg = "^out";
   						if (mb_ereg($reg,$param)) {
   							$outcomeids[]=$value;
   						}					
    				}
    				// Getting default values for display, category, teacher scale and student scale
    				$default_display = optional_param('default_display', null, PARAM_INT);
    				$default_category = optional_param('default_categ', 0, PARAM_INT);
    				//$default_teacherscale = optional_param('default_teacherscale', 0, PARAM_INT);
    				$default_studentscale = optional_param('default_studentscale', null, PARAM_INT);
    				// Updating 
    				if (!empty($outcomeids)) {
    					foreach ($outcomeids as $outcomeid) {
	    					
    						//check that no entry already exists
    						if ($gitem = $DB->get_record('grade_items',array('courseid' => $this->course->id,'outcomeid' => $outcomeid,'itemmodule' => $this->cm->modname, 'iteminstance' => $this->cm->instance))) {
    							// entry already exists for this outcome
    							break;
    						}
    						
    						// Getting corresponding outcome
	    					$outcome = $DB->get_record('grade_outcomes', array('id' => $outcomeid));
	    					
	    					// Create and insert new grade item
	    					$gitem = new stdClass();
	    					$gitem->courseid = $this->course->id;
	    					// Get course catgeory
	    					$categoryid = $DB->get_field('grade_categories', 'id',array('courseid' => $this->course->id));
	    					if ($categoryid != null) {
	    						$gitem->categoryid = $categoryid;
	    					}	    					
	    					$gitem->itemname = $outcome->fullname;
	    					$gitem->itemtype = 'mod';
	    					$gitem->itemmodule = 'checkoutcome';
	    					$gitem->iteminstance = $this->cm->instance;	    					
	    					$gitem->scaleid = $outcome->scaleid;
	    					$gitem->outcomeid = $outcomeid;
	    					$gitem->gradetype = 2;
	    					$gitem->timecreated = $gitem->timemodified = time();
	    					if ($gitemid = $DB->insert_record('grade_items', $gitem)) {
	    						
	    						// Create and insert new checkoutcome item
	    						$ch_item = new stdClass();
	    						$ch_item->checkoutcome = $this->checkoutcome->id;	    						
	    						$ch_item->itemnumber = $this->getCheckoutcomeNewItemNumber();
	    						$ch_item->gradeitem = $gitemid;
	    						if ($default_studentscale) {
	    							$ch_item->scaleid = $default_studentscale; 
	    						} else {
	    							$ch_item->scaleid = $outcome->scaleid;
	    						}
	    						if ($default_display && $default_display != 'NA') {
	    							$ch_item->display = $default_display;
	    						}
	    						$ch_item->category = $default_category;	    							    					
	    						$ch_item->timecreated = $ch_item->timemodified = time();
	    						if ($ch_itemid = $DB->insert_record('checkoutcome_item', $ch_item)) {
	    							// update list items
	    							$this->get_items();
	    						}
	    					}    						
    					}
    				}    				
    				break;
    			case 'deleteoutcomes':
    				if ($CFG->version < 2011120100) {
    					$itemids = optional_param('itemids', array(), PARAM_INT);
    				} else {
    					$itemids = optional_param_array('itemids', array(), PARAM_INT);
    				}
	    			if (!empty($itemids)) {
    					foreach ($itemids as $itemid) {
    						$item = $this->items[$itemid];// get item
	    					// Delete item only if not used
	    					if ($this->itemInUse($item) == 0) {
	    						// Delete grade item
	    						$DB->delete_records('grade_items', array('id' => $item[0]->id));
	    						// Delete checkoutcome item
	    						$DB->delete_records('checkoutcome_item', array('id' => $item[1]->id));
	    					}
	    				}
	    			}
    				// Reload items list
    				$this->get_items();
    				break;
    			case 'updateoutcomes':
    				$category  = required_param('category', PARAM_INT);  // category
    				$display  = required_param('display', PARAM_INT);  // display
    				if ($CFG->version < 2011120100) {
    					$itemids = optional_param('itemids', array(), PARAM_INT);
    				} else {
    					$itemids = optional_param_array('itemids', array(), PARAM_INT);
    				}
    				$this->updateoutcomes($itemids, $category, $display);
    				$this->get_items();
    				break;
    			case 'updateLink':
    				$itemid = required_param('itemid', PARAM_INT);
    				$linkurl = required_param('linkurl', PARAM_TEXT);
    				$this->update_item_link($itemid, $linkurl);
    				break;
    			case 'deleteLink':
    				$itemid = required_param('itemid', PARAM_INT);
    				$this->delete_item_link($itemid);
    				break;
    			case 'updateCountgoals':
    				if ($CFG->version < 2011120100) {
    					$newgoals = optional_param('items', array(), PARAM_INT);
    				} else {
    					$newgoals = optional_param_array('items', array(), PARAM_INT);
    				}
    				$this->update_count_goals($newgoals);
    				break;
    		}
    	}
    }
    
    /**
     * Process actions of the list_category screen
     */
    function process_list_cat_actions() {
    	global $DB;
    	 
    	$action  = optional_param('action', 0, PARAM_ALPHA);  // action
    	$categoryid  = optional_param('categoryid', 0, PARAM_INT);  // category ID
    	 
    	if ($action) {
    
    		switch ($action) {
    			case 'deleteCategory':
    				$DB->delete_records('checkoutcome_category', array('id'=>$categoryid));
    				break;
    		}
    	}
    }
    
    /**
     * Process actions of the list_gradings screen
     */
    function process_list_gradings_actions() {
    	global $DB;
    
    	$group  = optional_param('group', 0, PARAM_INT);  // action

        if ($group) {
    
    		switch ($action) {
    			case 'deleteCategory':
    				$DB->delete_records('checkoutcome_category', array('id'=>$categoryid));
    				break;
    		}
    	}
    }
    
    /**
     * Process actions of the list_display screen
     */
    function process_list_disp_actions() {
    	global $DB;
       	$action  = optional_param('action', 0, PARAM_ALPHA);  // action
    	$displayid  = optional_param('displayid', 0, PARAM_INT);  // display ID
    
    	if ($action) {
    
    		switch ($action) {
    			case 'deleteDisplay':
    				$DB->delete_records('checkoutcome_display', array('id'=>$displayid));
    				break;
    		}
    	}
    }
    
    /**
     * Process actions of the list_periods screen
     */
    function process_list_period_actions() {
    	global $DB, $CFG;
    	$action  = optional_param('action', 0, PARAM_ALPHA);  // action
    	$periodid  = optional_param('periodid', 0, PARAM_INT);  // period ID
    	if ($action) {    
    		switch ($action) {
    			case 'deletePeriod':
    				$DB->delete_records('checkoutcome_periods', array('id'=>$periodid));
    				$this->get_items();
    				break;
				case 'updateperiods':
    				$lock  = required_param('lock', PARAM_INT);  // lock
    				if ($CFG->version < 2011120100) {
    					$periods = optional_param('periods', array(), PARAM_INT);
    				} else {
    					$periods = optional_param_array('periods', array(), PARAM_INT);
    				}
    				$this->updateperiods($periods, $lock);
    				$this->get_items();
    				break;
    		}
    	}
    }
    
    /**
     * Process actions of the period goals screen
     */
    function process_periodgoals_actions() {
    	// To do
    }
     
     /**
     * Displays view screen
     */
    function view_items() {
    	global $DB,$PAGE,$OUTPUT,$CFG;
    	$thispage = new moodle_url('/mod/checkoutcome/view.php');
    	$update_com_page = new moodle_url('/mod/checkoutcome/updatecomment.php');
    	$add_doc_page = new moodle_url('/mod/checkoutcome/add_document.php');

    	// Get student
    	$student = null;
    	if ($this->studentid){
    		$student = $DB->get_record('user', array('id' => $this->studentid));
    	} else {
    		$student = $DB->get_record('user', array('id' => $this->userid));
    	}
    	if (empty($student)) {
    		print_error('nostudentfound','checkoutcome');
    	}
		
		if(!isset($this->selected_period->description))
		{
			$this->selected_period->description='';
		}
    	
    	// Display module title or student name if teacher is grading
    	if ($this->studentid){
    		echo '<a href="'.new moodle_url('/mod/checkoutcome/list_gradings.php', array('id' => $this->cm->id, 'group' => $this->currentgroup, 'selected_periodid' => $this->selected_period->id), 'Retour').'" class="backlink" onClick="javascript:M.mod_checkoutcome.check_beforeunload();">'.get_string('backtolist','checkoutcome').'</a>';
    		echo $OUTPUT->heading($student->firstname.' '.$student->lastname.' ('.$student->username.')',1);
    	} else {    		
    		echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);
    	}   	
    	 
    	$goalid = 0;
    	if ($goal = $DB->get_record('checkoutcome_period_goals', array('userid' => $student->id, 'period' => $this->selected_period->id))) {
    		$goalid = $goal->id;
    	}
    	
    	echo '<div class="generalbox">';
	    
    	echo '<div id="pageContent" class="pagecontent">';
    	
    	echo '<div class="title_block">';
    	$options = array();
    	foreach ($this->periods as $period) {
    		$options[$period->id] = $period->name;
    	}
    	if ($this->isGradingByStudent()) {
    		echo $OUTPUT->single_select(new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id, 'studentid' => $this->studentid)), 'selected_periodid', $options, $this->selected_period->id);
    	}
    	    	
    	
    	echo '<div class="display_period" title="'.$this->selected_period->description.'">';
	    	// Display period infos if not default period
	    	if (!$this->isDefaultPeriod()) {
					if($this->selected_period->startdate!=null && $this->selected_period->startdate!=0)
					{
						echo $OUTPUT->heading($this->selected_period->name.' ('.$this->date_fr('d M Y',$this->selected_period->startdate).' - '.$this->date_fr('d M Y',$this->selected_period->enddate).')');
					}
					else
					{
						echo $OUTPUT->heading($this->selected_period->name);
					}
					echo $OUTPUT->heading($this->selected_period->description, 3);
			}	    	
    	echo '</div>';
    	// Display controls
    	$controls ='<div class="application_controls">';
    	// Display link to view goals of the period if existing
    	if ($this->isGradingByTeacher()) {
    		$controls .= '<a id="period_goal" href="'.new moodle_url('/mod/checkoutcome/periodgoals.php', array('checkoutcome'=>$this->checkoutcome->id,'studentid'=>$student->id, 'selected_periodid' => $this->selected_period->id, 'goalid' => $goalid)).'" class="periodgoal" title="'.get_string('period_goals','checkoutcome').'"><img src="pix/award_star_gold_2.png"></a>';
    	} else if ($this->isGradingByStudent()) {
    		$controls .= '<a id="period_goal" href="'.new moodle_url('/mod/checkoutcome/periodgoals.php', array('checkoutcome'=>$this->checkoutcome->id, 'selected_periodid' => $this->selected_period->id, 'goalid' => $goalid)).'" class="periodgoal" title="'.get_string('period_goals','checkoutcome').'"><img src="pix/award_star_gold_2.png"></a>';
    	} else if (!$this->isPreview()) {
    		$controls .= '<a id="period_goal" href="'.new moodle_url('/mod/checkoutcome/periodgoals.php', array('checkoutcome'=>$this->checkoutcome->id,'studentid'=>$student->id, 'selected_periodid' => $this->selected_period->id, 'goalid' => $goalid)).'" class="periodgoal" title="'.get_string('period_goals','checkoutcome').'"><img src="pix/award_star_gold_2.png"></a>';
    	}
    	// Get student description of the period if any
    	if ($this->isGradingByStudent()) {
    		 
    		if (!$studentdescription = $DB->get_field('checkoutcome_period_goals', 'studentsdescription', array('period' => $this->selected_period->id, 'userid' => $this->userid))) {
    			$controls.= '<a id="addStudentDescription" title="'.get_string('add_student_description','checkoutcome').'" href="'.new moodle_url('/mod/checkoutcome/studentdescription.php', array('checkoutcome'=>$this->checkoutcome->id, 'selected_periodid' => $this->selected_period->id)).'" class="studentdescriptionimg"><img src="pix/tag_blue_add.png"></a>';
    		} else {
    			$controls.= '<a id="editStudentDescription" title="'.get_string('edit_student_description','checkoutcome').'" href="'.new moodle_url('/mod/checkoutcome/studentdescription.php', array('checkoutcome'=>$this->checkoutcome->id, 'selected_periodid' => $this->selected_period->id)).'" class="studentdescriptionimg"><img src="pix/tag_blue_edit.png"></a>';
    		}
    	}
    	// export pdf to portofolio
    	/*if (!empty($CFG->enableportfolios) && $this->isGradingByStudent()) {
    		require_once($CFG->libdir.'/portfoliolib.php');
    		$button = new portfolio_add_button();
    		$button->set_callback_options('checkoutcome_portfolio_caller', array('userid' => $this->userid, 'checkoutcomeid' => $this->checkoutcome->id, 'ispdffile' => 1, 'contextid' => $this->context->id, 'selected_periodid' => $this->selected_period->id, 'goalid' => $goalid), '/mod/checkoutcome/portofolio/locallib_portofolio.php');
    		$button = $button->to_html(PORTFOLIO_ADD_FULL_FORM, get_string('exportpdftoportfolio', 'checkoutcome'));
    		 
    		$buttonextraclass = '';
    		if (empty($button)) {
    			// no portfolio plugin available.
    			$button = '&nbsp;';
    			$buttonextraclass = ' noavailable';
    		}
    		$controls.= html_writer::tag('span', $button, array('class' => 'discussioncontrol exporttoportfolio'.$buttonextraclass));
    	}*/
    	// export PDF button
    	if ($this->isGradingByStudent()) {
    		$controls.= '<a id="exportPDF" title="'.get_string('export_pdf','checkoutcome').'" href="'.new moodle_url('/mod/checkoutcome/view.php', array('checkoutcome'=>$this->checkoutcome->id,'action'=>'exportPDF', 'selected_periodid' => $this->selected_period->id, 'goalid' => $goalid)).'" class="exportpdf"><img src="pix/acrobat.png"></a>';
    	} else if ($this->studentid != null) {
    		$controls.= '<a id="exportPDF" title="'.get_string('export_pdf','checkoutcome').'" href="'.new moodle_url('/mod/checkoutcome/view.php', array('checkoutcome'=>$this->checkoutcome->id,'action'=>'exportPDF','studentid'=>$this->studentid, 'selected_periodid' => $this->selected_period->id, 'goalid' => $goalid)).'" class="exportpdf"><img src="pix/acrobat.png"></a>';
    	}
    	$controls.= '</div>';
    	echo $controls;
    	// End display controls
    	echo '</div>';    	
    	
    	// Display student description
    	if (!empty($goal) && !empty($goal->studentsdescription)) {
    		echo $OUTPUT->box_start();
    		echo '<fieldset id="student_description" class="studentdescription">'.$goal->studentsdescription.'</fieldset>';
    		echo $OUTPUT->box_end();
    	}
    	// End student description
    	
           	
    	// Display last modifications dates by student and by teacher    	 	
    	$lastdatestudent = null;
    	$lastdateteacher = null;
    	$teacherid = null;
    	
    	$lastdatestudent = $this->getLastStudentChangeDate();
    	$lastdateteacher = $this->getLastTeacherChange(1);
    	$teacherid = $this->getLastTeacherChange(2);
		
    	$teacher= null;
    	if ($teacherid != null) {
    		$teacher = $DB->get_record('user', array('id' => $teacherid));
    	}
    	
    	$lastdates = '<div id="lastdates" class="last_dates">';
    	if ($lastdatestudent != null) {
    		$lastdates .= '<div id="lastdatestudent">'.get_string('lastdatestudent','checkoutcome').$this->date_fr('d-M-Y H:i',$lastdatestudent).'</div>';
    	} else {
    		$lastdates .= '<div id="lastdatestudent"></div>';
    	}
    	if ($lastdateteacher != null) {
    		$lastdates .= '<div id="lastdateteacher">'.get_string('lastdateteacher','checkoutcome').$this->date_fr('d-M-Y H:i',$lastdateteacher);
    		if ($teacher != null) {
    			$lastdates .= ' ('.$teacher->firstname.' '.$teacher->lastname.')';
    		}
    		$lastdates .= '</div>';
    	} else {
    		$lastdates .= '<div id="lastdateteacher"></div>';
    	}    	
    	$lastdates.='</div>';    	
    	// Print block lastdates
    	echo $lastdates;   	
		
		
		if(!isset($this->selected_period->lockperiod))
		{
			$this->selected_period->lockperiod='';
		}
		
		
		//Print message if period is locked
		if($this->selected_period->lockperiod=='1')
		{
			echo '<div class="lock_period">'.get_string('lock','checkoutcome').'</div>';
		}
    	 
    	// create form if teacher is grading
    	if ($this->isGradingByTeacher()) {
    		echo '<form method="post" class="mform" action="'.$thispage->out(true,array('studentid' => $this->studentid,'group' => $this->currentgroup)).'">';
    		echo '<input type="hidden" value="updateoptions" name="action">';
    		echo '<input type="hidden" value="'.$this->cm->id.'" name="id">';
    		echo '<input type="hidden" value="'.$this->selected_period->id.'" name="selected_periodid">';
    	}
    	// Fill in category_items array
    	if (!empty($this->items)){	    	
	    	foreach ($this->items as $item) {
	    		foreach ($this->categories as $categ) {	    			
	    				if ($item[1]->category == $categ->id) {
	    					$this->category_items[$categ->id][] = $item;
	    					break;
	    				}	    			 			
	    		}
	    	}
    	}
		
    	$pageContent = '';
    	// Display items sorted by category
    	if ($this->category_items != null && !empty($this->category_items)){    		
    		
			if($this->selected_period->lockperiod!='1')
			{
				if ($this->studentid) {
					echo '<input id="listsave" type="submit" value="'.get_string('save_grades','checkoutcome').'" name="submit" class="save_grades" style="float:right;">';
					echo '&nbsp;';
				}
			}
			
            // for each category list items
    		$index = 1;
    		$i = 1;    		
    		foreach ($this->categories as $categ) {    			
    			
    			if (!empty($this->category_items[$categ->id])) {
    				
    				
	    			$pageContent.= '<div class="category_title">';
		    			if ($categ->id != 0) {
			    				$pageContent.= '<a href="#" onClick="javascript:M.mod_checkoutcome.expand('.$index.');">';
		    						$pageContent.= '<img id="img_categ'.$index.'" src="pix/switch_minus.png" class="img_categ"/>';
		    					$pageContent.= '</a>';
			    				$pageContent.= $index.'&nbsp-&nbsp';
			    		}
			    		$pageContent .= '<span title="'.$categ->description.'">' . $categ->name . '</span>';
			    		// export category to portofolio
			    		/*if (!empty($CFG->enableportfolios) && $this->isGradingByStudent()) {
			    			require_once($CFG->libdir.'/portfoliolib.php');
			    			$button = new portfolio_add_button();
			    			$button->set_callback_options('checkoutcome_portfolio_caller', array('userid' => $this->userid, 'checkoutcomeid' => $this->checkoutcome->id, 'categoryid' => $categ->id, 'contextid' => $this->context->id, 'selected_periodid' => $this->selected_period->id), '/mod/checkoutcome/portofolio/locallib_portofolio.php');
			    			$button = $button->to_html(PORTFOLIO_ADD_FULL_FORM, get_string('exportcategorytoportfolio', 'checkoutcome'));
			    		
			    			$buttonextraclass = '';
			    			if (empty($button)) {
			    				// no portfolio plugin available.
			    				$button = '&nbsp;';
			    				$buttonextraclass = ' noavailable';
			    			}
			    			$pageContent.= html_writer::tag('span', $button, array('class' => 'categorycontrol exporttoportfolio'.$buttonextraclass));
			    		}*/
			    		// end export button
	    			$pageContent.= '</div>';   			
	    			    				
    				$pageContent.= '<div id="category'.$index.'" class="div_category">';
							
    				// Table definition
    				$data=array();
    				
    				foreach ($this->category_items[$categ->id] as $item) {
    					$line=array();
    					
    					// Define display color and display name
    					if ($item[1]->display) {
    						$disp = $DB->get_record('checkoutcome_display',array('id' => $item[1]->display));
    						$bgcolor = '#'.$disp->color;
    						if ($disp->iswhitefont) {
    							$fontcolor = '#fff';
    						} else {
    							$fontcolor = '#000';
    						}
    						$dispname = $disp->name;
    					} else {
    						$bgcolor = '#fff';
    						$fontcolor = '#000';
    						$dispname = 'no display';
    					}
    					
    					// First box : Item Number
    					$box = '';
    					$box .= '<div class="shortname" title="'.$item[4]->description.'">'.$item[4]->shortname.'</div>';
    					$line[] = $box;
    					//End First box    					
    					// Second box : Item Name + student's comment and documents + teacher's comment 
    					$box = '';
	    				$box .= '<div id="ch_item_'.$item[1]->id.'"  style="background-color:'.$bgcolor.';color:'.$fontcolor.'" class="div_view_item" title="'.$dispname.'">';
		    				// Item full name
		    				$box .= '<span class="item_title">'.$item[4]->fullname.'</span>';		    					
							// add link to resource
		    				if ($item[1]->resource != null) {
		    					$box .= '<a class="item_icon" href="'.$item[1]->resource.'" target="_new" title="'.get_string('view_details','checkoutcome').'">';
		    					$box .= '<img src="pix/page_white_magnify.png"/>';
		    					$box .= '</a>';
		    				}	    					
	    					// add student's addComment link if no comment , else add editComment link and deleteComment link
		    				if ($this->canupdateown() && $this->selected_period->lockperiod!='1') {
			    				if ($item[2]) {			    				
									if(empty($item[2]->comment)) {			    				
				    					$box .= '<a id="add_comment_'.$item[1]->id.'" name="add_comment" class="item_icon" href="#ch_item_'.$item[1]->id.'" title="'.get_string('add_comment','checkoutcome').'">';
					    					$box .= '<img src="pix/comment_add.png"/>';
					    				$box .= '</a>';
				    				} 
			    				} else {
			    					$box .= '<a id="add_comment_'.$item[1]->id.'" name="add_comment" class="item_icon" href="#ch_item_'.$item[1]->id.'" title="'.get_string('add_comment','checkoutcome').'">';
			    					$box .= '<img src="pix/comment_add.png"/>';
			    					$box .= '</a>';
			    				}			    				
		    				} else if ($this->studentid && $this->selected_period->lockperiod!='1'){
		    					// add teacher's addComment link if no comment , else add editComment link and deleteComment link
		    					if ($item[3]) {
		    						if(empty($item[3]->comment)) {
		    							$box .= '<a id="add_teacher_comment_'.$item[1]->id.'" name="add_teacher_comment" class="item_icon" href="#ch_item_'.$item[1]->id.'" title="'.get_string('add_comment','checkoutcome').'">';
		    							$box .= '<img src="pix/comment_add.png"/>';
		    							$box .= '</a>';
		    						}
		    					} else {
		    						$box .= '<a id="add_teacher_comment_'.$item[1]->id.'" name="add_teacher_comment" class="item_icon" href="#ch_item_'.$item[1]->id.'" title="'.get_string('add_comment','checkoutcome').'">';
		    						$box .= '<img src="pix/comment_add.png"/>';
		    						$box .= '</a>';
		    					}
		    				}
		    				// add student comment div		    				
		    				if ($item[2] && !empty($item[2]->comment)) {
		    					$box .= '<div id="div_comment_'.$item[1]->id.'" class="div_comment">';
		    					$box .= get_string('student_comment','checkoutcome');
		    					// display comment
		    					$box .= '<div id="comment_text_'.$item[1]->id.'" class="comment_text" name="comment_text">';
		    					//comment
		    					$box .= $item[2]->comment;
		    					$box .= '</div> [' . $this->date_fr('d-M-Y H:i',$item[2]->commenttime) . '] ';
		    					// show links if user is student
		    					if ($this->canupdateown() && $this->selected_period->lockperiod!='1') { 
			    					// comment edition link
			    					$title = '"'.get_string('edit_comment','checkoutcome').'"';
			    					$box .= '<a id="edi_comment_'.$item[1]->id.'" name="edit_comment" class="item_icon" href="#ch_item_'.$item[1]->id.'">';
			    					$box .= '<img src="'.$OUTPUT->pix_url('/t/edit').'"  alt='.$title.' title='.$title.' />';
			    					$box .= '</a>&nbsp';
			    					// comment delete link
			    					$title = '"'.get_string('delete_comment','checkoutcome').'"';
			    					$update_com_page->set_anchor('ch_item_'.$item[1]->id);			    					
			    					$box .= '<a class="item_icon" href="'.$update_com_page->out(true, array('action'=>'deleteComment','checkoutcome'=>$this->checkoutcome->id,'itemid'=>$item[2]->id, 'selected_periodid' => $this->selected_period->id)).'" onClick="return confirm(\''.get_string('delete_comment_confirm','checkoutcome').'\')">';
			    					$box .= '<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' /></a>';
			    					$box .= '</a>&nbsp';
			    					// add document link
			    					//$add_doc_page->set_anchor('ch_item_'.$item[1]->id);
			    					$box .= '<a name ="add_document" class="add_document_icon" href="'.$add_doc_page->out(true, array('checkoutcome'=>$this->checkoutcome->id,'itemid'=>$item[2]->id, 'chitemid'=>$item[1]->id, 'selected_periodid' => $this->selected_period->id)).'" title="'.get_string('add_document','checkoutcome').'">';
			    					$box .= '<img src="pix/attach.png"/>';
			    					$box .= '</a>';
		    					}		    					
		    					// get documents if any and display them
		    					$documents = $DB->get_records('checkoutcome_document', array('gradeid' => $item[2]->id));
		    					if ($documents) {
		    						$box .= '<div class="div_document_list"><div class="document_title">'.get_string('attached_documents','checkoutcome').'</div>';
		    						foreach ($documents as $doc) {
		    							$info = '';
		    							if (!empty($doc->description)) {
		    								$info = $doc->description;
		    							}
		    							if ($doc->url) {
		    								if (!mb_ereg("http",$doc->url)){ // fichier telecharge
		    									// l'URL a été correctement formée lors de la création du fichier
		    									$efile =  $CFG->wwwroot.'/pluginfile.php'.$doc->url;
		    								}
		    							} else{
		    								$efile = $doc->url;
		    							}
		    							$box .= '<div title="'.$info.'" class="div_document">';
		    							$box .= '<a href="'.$efile.'">'.$doc->title .'</a> [' . $this->date_fr('d-M-Y H:i',$doc->timemodified) . '] ';
		    							if ($this->canupdateown() && $this->selected_period->lockperiod!='1') {
			    							// add edit document link
			    							$title = '"'.get_string('edit_document','checkoutcome').'"';
			    							$box .= '<a name ="edit_document" class="item_icon" href="'.$add_doc_page->out(true, array('checkoutcome'=>$this->checkoutcome->id,'itemid'=>$item[2]->id,'chitemid'=>$item[1]->id, 'documentid' => $doc->id, 'selected_periodid' => $this->selected_period->id)).'">';
			    							$box .= '<img src="'.$OUTPUT->pix_url('/t/edit').'" alt='.$title.' title='.$title.' /></a>';
			    							$box .= '</a>';
			    							// add delete document link
			    							$thispage->set_anchor('ch_item_'.$item[1]->id);
			    							$title = '"'.get_string('delete_document','checkoutcome').'"';
			    							$box .= '<a name ="delete_document" class="item_icon" href="'.$thispage->out(true, array('action' => 'deleteDocument', 'checkoutcome'=>$this->checkoutcome->id,'itemid'=>$item[2]->id,'documentid' => $doc->id, 'selected_periodid' => $this->selected_period->id)).'" onClick="return confirm(\''.get_string('delete_document_confirm','checkoutcome').'\')">';
			    							$box .= '<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' /></a>';
			    							$box .= '</a>';			    							
		    							}
		    							$box .= '</div>';
		    						}
		    						$box .= '</div>';
		    					}
		    					$box .= '</div>';
		    				}
		    				
		    				// add teacher comment div		    				
		    				if ($item[3] && !empty($item[3]->comment)) {
		    					$box .= '<div id="div_teacher_comment_'.$item[1]->id.'" class="div_comment">';
		    					$box .= get_string('teacher_feedback','checkoutcome');
		    					// display teacher comment
		    					$box .= '<div id="teacher_comment_text_'.$item[1]->id.'" class="teacher_comment_text" name="teacher_comment_text">';
		    					//comment
		    					$box .= $item[3]->comment;
		    					$box .= '</div>';
		    					$feedbackhisto = $this->getFeedbackHisto($item[3]);
		    					if ($feedbackhisto) {
		    						$box .= ' [' . $this->date_fr('d-M-Y H:i',$feedbackhisto->timecreated) . ']';
		    						if ($feedbackhisto->username) {
		    							$box .= ' '.get_string('by','checkoutcome').' '.$feedbackhisto->username;
		    						}
		    					}
		    					//show links if user is teacher and is grading (not shown on preview)
		    					if (!$this->canupdateown() && $this->studentid && $this->selected_period->lockperiod!='1') {
			    					// teacher comment edition link
			    					$title = '"'.get_string('edit_comment','checkoutcome').'"';
			    					$box .= '<a id="edi_teacher_comment_'.$item[1]->id.'" name="edit_teacher_comment" class="item_icon" href="#ch_item_'.$item[1]->id.'">';
			    					$box .= '<img src="'.$OUTPUT->pix_url('/t/edit').'"  alt='.$title.' title='.$title.' />';
			    					$box .= '</a>&nbsp';
			    					// teacher comment delete link
			    					$update_com_page->set_anchor('ch_item_'.$item[1]->id);
			    					$title = '"'.get_string('delete_comment','checkoutcome').'"';
			    					$box .= '<a class="item_icon" href="'.$update_com_page->out(true, array('action'=>'deleteteacherComment','checkoutcome'=>$this->checkoutcome->id,'itemid'=>$item[3]->id, 'studentid' => $this->studentid, 'group' => $this->currentgroup, 'selected_periodid' => $this->selected_period->id)).'" onClick="return confirm(\''.get_string('delete_comment_confirm','checkoutcome').'\')">';
			    					$box .= '<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' /></a>';
			    					$box .= '</a>&nbsp';
		    					}
		    					$box .= '</div>';
		    				}
		    				
		    			$line[] = $box;
		    			// End Second box
		    			//if (!$this->isPreview()) {
						if ($this->isGradingByStudent() || $this->isGradingByTeacher()) {
		    			// Begin Third box : counter
		    			$box = '';
		    			if ($item[1]->countgoal != 0) {
		    				$count = 0;
		    				if ($item[2] != null) {
		    					$count = $item[2]->count;
		    				}
		    				if ($this->isGradingByStudent() && $this->selected_period->lockperiod!='1') {
		    					$box = '<img id="counter_plu_'.$item[1]->id.'" class="counter_plus" src="pix/switch_plus.png"/>'
		    					.'<input id="countervalue_'.$item[1]->id.'" type="text" class="counter_value" value="'.$count.'" maxlength="3"/>'
		    					.'<img id="counter_min_'.$item[1]->id.'" class="counter_minus" src="pix/switch_minus.png"/>';
		    				} else {
		    					$box = '<span id="countervalue_'.$item[1]->id.'">'.$count.'</span>';
		    				}
		    				
		    				$box.='<span id="div_countergoal" class="countergoal">/'.$item[1]->countgoal.'</span>';
		    			}
		    			$line[] = $box;
		    			// End Third box : counter		    			
			    			// Fourth box : Student scale
			    			$box = '';
			    			/*if ($this->canupdateother()) {		    					
			    				$id = 'teacher';
			    			} else {
			    				$id = 'selstu_'.$item[1]->id;
			    			}*/
							if($this->isGradingByStudent())
							{
								$id = 'selstu_'.$item[1]->id;
							}
							if ($this->isGradingByTeacher()) {		    					
			    				$id = 'teacher';
			    			}
			    			// Getting scale items
			    			$scale = $DB->get_record('scale',array('id' => $item[1]->scaleid));
			    			$scaleitems = mb_split(",",$scale->scale);
			    			
			    			
			    			
			    			if ($this->isGradingByStudent() && !$this->teacherHasGraded($item) && $this->selected_period->lockperiod!='1') {
			    				// add student scale options
			    				$box.= '<select class="select_view_items" id="'.$id.'" name="items[]">';
				    				// add first item
			    					$box .= '<option value="-1">';
			    						$box .= get_string('select_item','checkoutcome');
			    					$box .= '</option>';
			    					$j = 1;  			    				
				       				foreach ($scaleitems as $scaleitem) {
				       					$selected = '';
				       					if ($this->isSelectedScaletitem($item,$j)) {
				       						$selected = 'selected';
				       					}
				    					$box .= '<option '.$selected.' value="'.$j.'">';
				    						$box .= '<label for="item'.$i.'">'.$scaleitem.'</label>';
				    					$box .= '</option>';
				    					$j++;
				    				}
			    				$box .= '</select>';
			    			} else {
			    				$box.= '<div class="div_view_items" id="'.$id.'">';
			    				$j = 1;
			    				foreach ($scaleitems as $scaleitem) {
			    					if ($this->isSelectedScaletitem($item, $j)) {
			    						$box.= $scaleitem;
			    						break;
			    					}
			    					$j++;
			    				}
			    				$box.='</div>';
			    			}
			    			// show date
			    			if ($item[2] != null && $item[2]->grade !=null && $item[2]->grade != 0) {
			    				$gradehisto = $this->getSelfGradeHisto($item[2]);
			    				//$box .= '<img class="isgraded" src="pix/tick.png" title="'.get_string('graded_item','checkoutcome').'">';
			    				$box .= '<div class="isgraded">['.$this->date_fr('d-M-Y H:i',$gradehisto->timecreated).'] </div>';
			    			}
			    			
			    			$line[] = $box;
			    			// End Fourth box
			    			// Fifth box : Teacher scale
			    			$box = '';	 
							$id = 'seltea_'.$item[1]->id;
			    			// Getting teacher scale items
			    			$teacherscale = $DB->get_record('scale',array('id' => $item[1]->scaleid));
			    			$teacherscaleitems = mb_split(",",$teacherscale->scale);
			    			
			    			//$box.= '<div class="div_view_items">';
			    			if ($this->isGradingByTeacher() && $this->selected_period->lockperiod!='1'){
			    				// add teacher scale options
			    				$box .= '<select class="select_view_items" id="'.$id.'" name="items[]">';
			    				// add first item
			    				$box .= '<option value="-1">';
			    				$box .= get_string('select_item','checkoutcome');
			    				$box .= '</option>';
			    				$j = 1;
			    				$isSelected = null;
			    				foreach ($teacherscaleitems as $scaleitem) {
			    					if ($this->isSelectedTeacherScaleitem($item,$j)) {
			    						$isSelected = true;
			    						break;
			    					}
			    					$j++;
			    				}
			    				$q = 1;
			    				foreach ($teacherscaleitems as $scaleitem) {
			    					$selected = '';
			    					if ($isSelected != null && $q == $j) {
			    						$selected = 'selected';
			    					}
			    					if ($isSelected == null && $this->isSelectedScaletitem($item, $q)) {
			    						$selected = 'selected';
			    					}
			    					$box .= '<option '.$selected.' value="'.$q.'">';
			    					$box .= '<label for="item'.$i.'">'.$scaleitem.'</label>';
			    					$box .= '</option>';
			    					$q++;
			    				}
			    				$box .= '</select>';			    				
			    			} else {	
								$box.= '<div class="div_view_items">';
				    			// display teacher grade				    			
				    			$j = 1;
				    			foreach ($teacherscaleitems as $scaleitem) {
				    				if ($this->isSelectedTeacherScaleitem($item, $j)) {
				    					$box.= $scaleitem;
				    					break;
				    				}
				    				$j++;
				    			}
								$box.='</div>';								
			    			}
			    			// show date
			    			if ($item[3] != null && $item[3]->grade !=null && $item[3]->grade != 0) {
			    				$gradehisto = $this->getGradeHisto($item[3]);
			    				$box .= '<img class="isgraded" src="pix/tick.png" title="'.get_string('graded_item','checkoutcome').'">';
			    				$box .= '<div class="isgraded">['.$this->date_fr('d-M-Y H:i',$gradehisto->timecreated).'] '.get_string('by','checkoutcome').' '.$gradehisto->username.'</div>';
			    			}
			    			 
			    			//$box.='</div>';
			    			
			    			$line[] = $box;
			    			// End Fifth box
		    			}
						else
						{
							// Begin Third box : counter
							$box = '';
							if ($item[1]->countgoal != 0) {							
								$box.='<span id="div_countergoal" class="countergoal">'.$item[1]->countgoal.'</span>';
							}
							$line[] = $box;
							
							// Fourth box : scale
							$box = '';
							$box.= '<div class="div_view_items" >';
			    			$j = 1;
							// Getting scale items
			    			$scale = $DB->get_record('scale',array('id' => $item[1]->scaleid));
			    			$scaleitems = mb_split(",",$scale->scale);
			    			foreach ($scaleitems as $scaleitem) {
			    				$box.= $scaleitem.', ';
			    				$j++;
			    			}
			    			$box.='</div>';
							$line[] = $box;
						}
		    			$data[] = $line;
	    				$i++;	    				
    				}
    				$table = new html_table();
    				$strshortname = get_string('shortname','checkoutcome');
    				$strfullname = get_string('fullname','checkoutcome');
    				$strcounter = get_string('counter','checkoutcome');
    				//if (!$this->isPreview()) {
					if ($this->isGradingByStudent() || $this->isGradingByTeacher()) {
						$table->head  = array($strshortname, $strfullname, $strcounter, get_string('student_grading','checkoutcome'),get_string('teacher_grading','checkoutcome'));
    					$table->size  = array('10%', '50%', '10%', '15%', '15%');
    					$table->align = array('center', 'left', 'center', 'center', 'center');
    					$table->width = '100%';
    				} else {
    					$table->head  = array($strshortname, $strfullname, get_string('countergoal','checkoutcome'), get_string('scale','checkoutcome'));
    					$table->size  = array('5%', '65%', '10%', '20%');
    					$table->align = array('center', 'left', 'center', 'left');
    					$table->width = '100%';
    				}    				
    				
    				$table->data  = $data;
    				$pageContent .= html_writer::table($table);
    				
    				// End Table definition
	    			
	    			$pageContent .= '</div>';
	    		$index++;
    			}    			    		
    		}
    		echo $pageContent;
    		
			if($this->selected_period->lockperiod!='1')
			{
				if ($this->studentid) {
					echo '<input id="listsave" type="submit" value="'.get_string('save_grades','checkoutcome').'" name="submit" class="save_grades" style="float:right;">';
				}
			}
     		if ($this->isGradingByTeacher()) {
     			echo '</form>';
     		}
     		echo '</div>';
     		echo '</div>';// ex fieldset
     		
    		
    	} else {
    		echo $pageContent .= get_string('empty_list','checkoutcome');
    		if ($this->isGradingByTeacher()) {
    			echo '</form>';
    		}
    		echo '</div>';
    		echo '</div>'; // ex fieldset    		
    	}
    	
    	//load JS
		if ($CFG->version < 2012120300) // < Moodle 2.4
		{ 
			$jsmodule = array(
					'name' => 'mod_checkoutcome',
					'fullpath' => new moodle_url('/mod/checkoutcome/checkoutcome.js'),
					'strings' => array(
							array('validate','checkoutcome'),
							array('cancel','checkoutcome'),
							array('savegrades','checkoutcome'),
							array('exportpdftoportfolio','checkoutcome'),
							array('exportcategorytoportfolio','checkoutcome'),
							array('maxlength','checkoutcome'),
							array('lastdatestudent','checkoutcome'),
							array('lastdateteacher','checkoutcome'),
					)
			);
			$PAGE->requires->yui2_lib('dom');
			$PAGE->requires->yui2_lib('event');
			$PAGE->requires->yui2_lib('connection');
			$PAGE->requires->yui2_lib('dragdrop');
			$PAGE->requires->yui2_lib('container');
			$PAGE->requires->yui2_lib('animation');
			$PAGE->requires->yui2_lib('yahoo');
			$PAGE->requires->yui2_lib('element');
			$PAGE->requires->yui2_lib('button'); 
			
			$serverurl = new moodle_url('/mod/checkoutcome');
			if ($this->isGradingByStudent()) {
				$PAGE->requires->js_init_call('M.mod_checkoutcome.init_view_items', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id, $this->selected_period->id), true, $jsmodule);
			} else {
				$PAGE->requires->js_init_call('M.mod_checkoutcome.init_view_items_teacher', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id, $this->studentid, $this->currentgroup, $this->selected_period->id), true, $jsmodule);
			}
		}
		else
		{
			$jsmodule = array(
					'name' => 'mod_checkoutcome',
					'fullpath' => new moodle_url('/mod/checkoutcome/checkoutcome24.js'),
					'strings' => array(
							array('validate','checkoutcome'),
							array('cancel','checkoutcome'),
							array('savegrades','checkoutcome'),
							array('exportpdftoportfolio','checkoutcome'),
							array('exportcategorytoportfolio','checkoutcome'),
							array('maxlength','checkoutcome'),
							array('lastdatestudent','checkoutcome'),
							array('lastdateteacher','checkoutcome'),
					)
			);
			
			$serverurl = new moodle_url('/mod/checkoutcome');
			if ($this->isGradingByStudent()) {
				$PAGE->requires->js_init_call('M.mod_checkoutcome.init', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id, $this->selected_period->id, '', '', '', '', '', '', 'init_view_items'), true, $jsmodule);
			} else {
				$PAGE->requires->js_init_call('M.mod_checkoutcome.init', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id, $this->selected_period->id, $this->studentid, $this->currentgroup, '', '', '', '', 'init_view_items_teacher'), true, $jsmodule);
			}
		}
    }
    
    /**
     * Displays edit screen
     */
    function view_edit() {
    	global $DB,$OUTPUT,$PAGE,$CFG;
    	
		// Urls
    	$thispage = new moodle_url('/mod/checkoutcome/edit.php');
    	$add_outcome_page = new moodle_url('/mod/checkoutcome/add_outcome.php');
    	
    	$imgediturl = $OUTPUT->pix_url('/t/edit');
    	$imgaddurl = $OUTPUT->pix_url('/t/add');
    	$imgdeleteurl = $OUTPUT->pix_url('/t/delete');
    	
    	
    	// Strings
    	$strshortname = get_string('shortname', 'checkoutcome');
    	$strfullname = get_string('fullname', 'checkoutcome');
    	$strteacherscale = get_string('teacher_scale', 'checkoutcome');
    	$strdisplay = get_string('display', 'checkoutcome');
    	$strmodule = get_string('category_choice', 'checkoutcome');
    	$strinuse = get_string('in_use','checkoutcome');
    	$stredit = get_string('edit_item','checkoutcome');
    	$strdelete = get_string('delete','checkoutcome');
    	$strlink = get_string('link','checkoutcome');
    	$strcounter = get_string('counter','checkoutcome');
    	
    	// Table definition
    	$data=array();
    	if (!empty($this->items)){
    		
    		
    		echo '<form method="post" class="mform" action="'.$thispage->out(true,null).'">';   		
    		
    		foreach ($this->items as $item) {
	    		$line=array();	    		

	    		// Checkbox
	    		$line[] = '<input type="checkbox" name="itemids[]" value="'.$item[1]->id.'"/>';

	    		// Short name
	    		$sname = NULL;
	    		if ($sname = $item[4]->shortname) {
	    			$line[] = $sname;
	    		} else {
	    			$line[] = get_string('NA','checkoutcome');
	    		}			
	    		    		
	    		// Full Name
	    		$name = NULL;
	    		if ($item[4]->fullname != null) {
	    			if ($item[1]->resource != null) {
	    				$line[] = '<a id="resourcelink_'.$item[1]->id.'" href="' . $item[1]->resource . '" target="_new">' . $item[4]->fullname . '</a>';	    				
	    			} else {
	    				$line[] = $item[4]->fullname;
	    			}
	    			
	    		} else {
	    			$line[] = get_string('NA','checkoutcome');
	    		}	    		
	    		
	    		/// return tracking object
	    		$gpr = new grade_plugin_return(array('page'=>$thispage));
	    		
	    		// Teacher scale
	    		$teacherscale = NULL;
	    		if ($teacherscale = $DB->get_record('scale', array('id' => $item[0]->scaleid))) {
	    			$line[] = $this->grade_print_scale_link($this->course->id, $teacherscale, $gpr);
	    		} else {
	    			$line[] = get_string('NA','checkoutcome');
	    		}    		
	    		
	    		// Category
	    		$categoryid = NULL;
	    		if ($categoryid = $item[1]->category) {
	    			$category = $DB->get_record('checkoutcome_category', array('id' => $categoryid));
	    			$line[] = $category->name;
	    		} else {
	    			$line[] = get_string('NA','checkoutcome');
	    		}
	    		
	    		// Display
	    		$displayid = NULL;
	    		if ($displayid = $item[1]->display) {
	    			$display = $DB->get_record('checkoutcome_display', array('id' => $displayid));
	    			$line[] = $display->name;
	    		} else {
	    			$line[] = get_string('NA','checkoutcome');
	    		}
	    		
	    		// Counter
				$line[] = '<img id="counter_plu_'.$item[1]->id.'" class="counter_plus" src="pix/switch_plus.png"/>'
	    		.'<input id="countervalue_'.$item[1]->id.'" type="text" class="counter_value" value="'.$item[1]->countgoal.'" maxlength="3"/>'
	    		.'<img id="counter_min_'.$item[1]->id.'" class="counter_minus" src="pix/switch_minus.png"/>';
	    		    		
	    		// Link
	    		if (!empty($item[1]->resource)) {
	    			$line[] = '<img id="resource_'.$item[1]->id.'_'.$item[4]->shortname.'" class="resource" src="'.$imgediturl.'"  alt="'.get_string('edit_link','checkoutcome').'" title="'.get_string('edit_link','checkoutcome').'" />'
	    						.'<img id="resourcedel_'.$item[1]->id.'_'.$item[4]->shortname.'" class="resourcedel" src="'.$imgdeleteurl.'"  alt="'.get_string('delete_link','checkoutcome').'" title="'.get_string('delete_link','checkoutcome').'" />';
	    		
	    		} else {
	    			$line[] = '<img id="resource_'.$item[1]->id.'_'.$item[4]->shortname.'" class="resource" src="'.$imgaddurl.'"  alt="'.get_string('add_link','checkoutcome').'" title="'.get_string('add_link','checkoutcome').'" />';	
	    		}
	    		
	    		
	    		// In use : count of grades using this item
					//Counting teacher grades on this item
	    			//$teachers_grades_count = $DB->count_records('grade_grades',array('itemid' => $item[0]->id));
	    		
	    			//Counting student grades on this item
	    			$students_grades_count = $DB->count_records('checkoutcome_selfgrading',array('checkoutcomeitem' => $item[1]->id));
				
	    			$inuse = $students_grades_count; 
	    			
	    		$line[] = $inuse;
	    		$data[] = $line;
	    		
	    	}
	    	
	    	$table = new html_table();
	    	$table->head  = array('',$strshortname, $strfullname, $strteacherscale, $strmodule, $strdisplay,$strcounter, $strlink, $strinuse);
	    	$table->size  = array('5%','10%', '30%', '10%', '15%', '15%','5%','5%','5%');
	    	$table->align = array('center', 'center', 'left', 'center','center','center','center','center','center');
	    	$table->width = '100%';
	    	$table->data  = $data;
	    	echo html_writer::table($table);
	    	
	    	// Select category and display 
	    	echo '<div class="generalbox nopaddingbottom">';
		    	// Select category
		    	echo get_string('category','checkoutcome'). ' : ' .
		    	'<select id="category" name="category">'.
		    	'<option value="-1">'.get_string('NA','checkoutcome').'</option>';
		    	$categories = $DB->get_records('checkoutcome_category',array('checkoutcome' => $this->checkoutcome->id),'id');
		    	foreach ($categories as $categ) {
		    		echo '<option value="'.$categ->id.'">'.$categ->name.'</option>';
		    	}
		    	echo '</select><br><br>';
		    	 
		    	// Select Display
		    	echo get_string('display','checkoutcome'). ' : ' .
		    	'<select id="display" name="display">'.
		    	'<option value="-1">'.get_string('NA','checkoutcome').'</option>';
		    	$displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $this->checkoutcome->id),'id');
		    	foreach ($displays as $disp) {
		    		echo '<option value="'.$disp->id.'">'.$disp->name.'</option>';
		    	}
		    	echo '</select><br><br>';
		    	echo '<input id="form_action" type="hidden" value="updateoutcomes" name="action">';
		    	echo '<input type="hidden" value="'.$this->cm->id.'" name="id">';
		    	echo '<input type="submit" name="submitoutcomes" class="save_grades" title="'.get_string('update_outcomes_desc','checkoutcome').'" value="'.get_string('update_outcomes','checkoutcome').'" onClick="document.getElementById(\'form_action\').value=\'updateoutcomes\';"/>';
		    echo '</div>';
		    echo '<input type="submit" name="submitoutcomes" class="viewedit" title="'.get_string('delete_outcomes_desc','checkoutcome').'" value="'.get_string('delete_outcomes','checkoutcome').'" onClick="document.getElementById(\'form_action\').value=\'deleteoutcomes\';return confirm(\''.get_string('delete_outcome_confirm','checkoutcome').'\')"/>';
	    	echo '</form>';
    	} else {
    		echo get_string('empty_list','checkoutcome').'<br/><br/>';
    	}
    	
    	// Form Add Outcomes
    	echo '<form id="add_outcome" action="'.$add_outcome_page->out_omit_querystring().'">';
	    	echo '<input type="hidden" value="'.$this->cm->id.'" name="id">';
	    	echo '<input type="submit" class="viewedit" title="'.get_string('add_outcome_desc','checkoutcome').'" value="'.get_string('add_outcome','checkoutcome').'"/>';
    	echo '</form>';   	
    	
    	//load JS
    	if ($CFG->version < 2012120300) // < Moodle 2.4
		{ 
			$jsmodule = array(
				'name' => 'mod_checkoutcome',
				'fullpath' => new moodle_url('/mod/checkoutcome/checkoutcome.js'),
				'strings' => array(
						array('validate','checkoutcome'),
						array('cancel','checkoutcome'),
						array('new_link_outcome','checkoutcome'),
						array('edit_link','checkoutcome'),
						array('add_link','checkoutcome'),
						array('delete_link','checkoutcome'),
						array('delete_link_outcome','checkoutcome'),
						array('delete_link_question','checkoutcome')
				)
			);
			$PAGE->requires->yui2_lib('dom');
			$PAGE->requires->yui2_lib('event');
			$PAGE->requires->yui2_lib('connection');
			$PAGE->requires->yui2_lib('dragdrop');
			$PAGE->requires->yui2_lib('container');
			$PAGE->requires->yui2_lib('animation');
			$PAGE->requires->yui2_lib('yahoo');
			$PAGE->requires->yui2_lib('element');
			$PAGE->requires->yui2_lib('button'); 
			
			$serverurl = new moodle_url('/mod/checkoutcome');
    	
			$PAGE->requires->js_init_call('M.mod_checkoutcome.init_edit_items', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id,$imgediturl->out(),$imgaddurl->out(),$imgdeleteurl->out()), true, $jsmodule);  
		}
		else
		{
			$jsmodule = array(
				'name' => 'mod_checkoutcome',
				'fullpath' => new moodle_url('/mod/checkoutcome/checkoutcome24.js'),
				'strings' => array(
						array('validate','checkoutcome'),
						array('cancel','checkoutcome'),
						array('new_link_outcome','checkoutcome'),
						array('edit_link','checkoutcome'),
						array('add_link','checkoutcome'),
						array('delete_link','checkoutcome'),
						array('delete_link_outcome','checkoutcome'),
						array('delete_link_question','checkoutcome')
				)
			);
			
			$serverurl = new moodle_url('/mod/checkoutcome');
    	
			$PAGE->requires->js_init_call('M.mod_checkoutcome.init', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id, '', '', '', $imgediturl->out(),$imgaddurl->out(),$imgdeleteurl->out(), '', 'init_edit_items'), true, $jsmodule); 
		}  	   	
    	
    }
    
    /**
     * Displays export screen
     */
    function view_export() {
    	global $OUTPUT;
    	
    	$mform = new checkoutcome_grade_export_form(null, array('includeseparator'=>true, 'publishing' => true, 'cmid' => $this->cm->id, 'itemmodule' => $this->cm->modname, 'iteminstance' => $this->cm->instance, 'periods' => $this->periods));
    	
    	$groupmode    = groups_get_course_groupmode($this->course);   // Groups are being used
    	$currentgroup = groups_get_course_group($this->course, true);
    	if ($groupmode == SEPARATEGROUPS and !$currentgroup and !has_capability('moodle/site:accessallgroups', $this->context)) {
    		echo $OUTPUT->heading(get_string("notingroup"));
    		echo $OUTPUT->footer();
    		die;
    	}
    	
    	// process post information
    	if ($data = $mform->get_data()) {
			if($data->selected_periodid==0)
			{
				$export = new checkoutcome_grade_export_txt($this->course, $currentgroup, '', false, false, false, $data->gradetype, 0, $data->gradesource, $data->separator,$this->cm->modname, $this->cm->instance, $this->checkoutcome->id, 0);
			}
			else
			{
				$export = new checkoutcome_grade_export_txt($this->course, $currentgroup, '', false, false, false, $data->gradetype, 0, $data->gradesource, $data->separator,$this->cm->modname, $this->cm->instance, $this->checkoutcome->id, $this->selected_period->id);
			}
    	
    		// print the grades on screen for feedback    	
    		$export->process_form($data);
    		$export->print_continue();
    		$export->display_preview();
    		echo $OUTPUT->footer();
    		exit;
    	}
    	
    	groups_print_course_menu($this->course, 'index.php?id='.$this->cm->id);
    	echo '<div class="clearer"></div>';
    	
    	$mform->display();
    }
    
    /**
     * Displays list_category screen
     */
    function view_list_cat() {
    	global $DB,$OUTPUT;
    	$thispage = new moodle_url('/mod/checkoutcome/list_cat.php');
    	$update_cat_page = new moodle_url('/mod/checkoutcome/update_cat.php');
    	$categories = $DB->get_records('checkoutcome_category',array('checkoutcome' => $this->checkoutcome->id),'shortname');
    	
		$strcategoryshortname = get_string('shortname','checkoutcome');
    	$strcategoryname = get_string('category','checkoutcome');
    	$strcategorydescription = get_string('category_description','checkoutcome');
    	$stredit = get_string('edit_category','checkoutcome');
    	
    	echo '<fieldset class="clearfix">';
    	if ($categories) {
    		$data = array();
	    	foreach ($categories as $cat) {
	    		$line = array();
				// Category shortname
	    		$line[] = $cat->shortname;
	    		// Category name
	    		$line[] = $cat->name;
	    		// Category description
	    		$line[] = $cat->description;
	    		// Edition link
	    		$title = '"'.get_string('editcategory','checkoutcome').'"';
	    		$links = '';
	    		$links .= '<a href="'.$update_cat_page->out(true, array('checkoutcome'=>$this->checkoutcome->id,'categoryid'=>$cat->id)).'">'
	    		.'<img src="'.$OUTPUT->pix_url('/t/edit').'"  alt='.$title.' title='.$title.' />'
	    		.'</a>';
	    		// Deletion link
	    		if ($this->categoryInUse($cat->id) == 0) {
	    			$title = '"'.get_string('delete_category','checkoutcome').'"';
	    			$links .= '&nbsp<a href="'.$thispage->out(true, array('action'=>'deleteCategory','checkoutcome'=>$this->checkoutcome->id,'categoryid'=>$cat->id)).'" onClick="return confirm(\''.get_string('delete_category_confirm','checkoutcome').'\')">'
	    			.'<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' />'
	    			.'</a>';
	    		}   		
	    		$line[] = $links;
	    		$data[] = $line;
	    	}
	    	
	    	$table = new html_table();
	    	$table->head  = array($strcategoryshortname, $strcategoryname, $strcategorydescription, $stredit);
	    	$table->size  = array('10%', '20%', '30%', '5%');
	    	$table->align = array('center', 'left', 'left', 'center');
	    	$table->width = '100%';
	    	$table->data  = $data;
	    	echo html_writer::table($table);
	    	
    	} else {
    		echo get_string('empty_category_list','checkoutcome');
    	}
    	
    	echo '</fieldset>';
    	echo '<form id="add_new" action="'.$update_cat_page->out_omit_querystring().'">';
    	echo '<input type="hidden" value="'.$this->cm->id.'" name="id">';
    	echo '<input type="submit" value="'.get_string('add_category','checkoutcome').'"></input>';
    	echo '</form>';
    	
    }
    

    /**
     * Displays summary screen
     */
    function view_summary() {
    	global $DB,$COURSE,$OUTPUT,$USER, $CFG;
    	$thispage = new moodle_url('/mod/checkoutcome/summary.php');
		$group=null;
		// Url parameters
		$criteria_student_group = optional_param('selected_studentgroupid', '0', PARAM_INT);
		$criteria_student = optional_param('selected_studentid', '0', PARAM_INT);
    	$criteria_period = optional_param('selected_periodid', '0', PARAM_INT);
    	$criteria_category = optional_param('criteria_category', '0', PARAM_INT);
    	$criteria_display = optional_param('criteria_display', '0', PARAM_INT);
		$criteria_valuetype = optional_param('criteria_valuetype', '0', PARAM_INT);
		
		if ($CFG->version < 2011120100) {
		    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		}
		else {
		    $context = context_course::instance($COURSE->id);
		}

		$query3 = 'select distinct(r.archetype) from {role} as r, {role_assignments} as ra where r.id=ra.roleid and ra.contextid='.$context->id.' and ra.userid=' . $USER->id . ';';
		$roles = $DB->get_records_sql( $query3 );
		$roleid='';
		foreach ($roles as $role) {
			$roleid=$role->archetype;
		}
		if($roleid!='student')
		{
			if($roleid=='teacher')
			{
				$query2 = 'select g.id, g.name from {groups} as g, {groups_members} as gm where g.courseid=' . $COURSE->id . ' and gm.userid=' . $USER->id . ' and g.id=gm.groupid;';
				
				$query2bis = 'select g.id from {groups} as g, {groups_members} as gm where g.courseid=' . $COURSE->id . ' and gm.userid=' . $USER->id . ' and g.id=gm.groupid LIMIT 1;';
				$groupeide = $DB->get_records_sql($query2bis);
				foreach ($groupeide as $groupid) {
					$group=$groupid->id;
				}
				$criteria_student_group = optional_param('selected_studentgroupid',$group , PARAM_INT);
			}
			else
			{
				$query2 = 'select id, name from {groups} where courseid=' . $COURSE->id . ';';
				$criteria_student_group = optional_param('selected_studentgroupid', '0', PARAM_INT);
			}
			$groups = $DB->get_records_sql( $query2 );

			//Get students
			
			
			if ($criteria_student_group == 0) 
			{
				$query = 'select u.id as id, username from {role_assignments} as a, {user} as u, {role} as r where a.contextid=' . $context->id . ' and r.archetype="student" and a.userid=u.id and r.id=a.roleid;';
			} 		
			else
			{
				$query = 'select u.id as id, username from {role_assignments} as a, {user} as u, {groups_members} as g, {role} as r where a.contextid=' . $context->id . ' and r.archetype="student" and g.groupid IN (select g.id from {groups} as g, {groups_members} as gm where g.courseid=' . $COURSE->id . ' and gm.userid=' . $USER->id . ' and g.id=gm.groupid) and u.id=g.userid and a.userid=u.id and r.id=a.roleid;';
			}
			$students = $DB->get_records_sql( $query );
		}
		else
		{
			$criteria_student_group=0;
			$criteria_student=$USER->id;
		}
		
    	echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter nopaddingbottom');
    	echo '<form id="get_summary" action="'.$thispage->out_omit_querystring().'#mean_results">';
    	echo '<div class="select_report">';
			if($roleid!='student')
			{
				// Criteria Student's group
				echo get_string('criteria_student_group','checkoutcome');
				echo '<select name="selected_studentgroupid">';
					if($roleid!='teacher')
					{
						echo '<option value="0" ';
						if ($criteria_student_group == 0) {
							echo 'selected';
						}
						echo '>'.get_string('all_student_groups','checkoutcome').'</option>';
					}

					foreach ($groups as $group) {
						if ($group->id != 0) {
							echo '<option value="'.$group->id.'" ';
							if ($criteria_student_group == $group->id) {
								echo 'selected';
							}
							echo '>'.$group->name.'</option>';
						}
					}
				echo '</select>';
				// Criteria Student
				echo '&nbsp'.get_string('criteria_student','checkoutcome');
				echo '<select name="selected_studentid">';
					echo '<option value="0" ';
					if ($criteria_student == 0) {
						echo 'selected';
					}
					echo '>'.get_string('all_students','checkoutcome').'</option>';

					foreach ($students as $student) {
						if ($student->id != 0) {
							echo '<option value="'.$student->id.'" ';
							if ($criteria_student == $student->id) {
								echo 'selected';
							}
							echo '>'.$student->username.'</option>';
						}
					}
				echo '</select>';
			}
	    	// Criteria Period
	    	echo '&nbsp'.get_string('criteria_period','checkoutcome');
	    	echo '<select name="selected_periodid">';
				echo '<option value="0" ';
    			if ($criteria_period == 0) {
    				echo 'selected';
    			}
    			echo '>'.get_string('all_periods','checkoutcome').'</option>';

	    	foreach ($this->periods as $period) {
	    		if ($period->id != 0) {
	    			echo '<option value="'.$period->id.'" ';
	    			if ($criteria_period == $period->id) {
	    				echo 'selected';
	    			}
	    			echo '>'.$period->name.'</option>';
	    		}
	    	}
	    	echo '</select>';    	
    		// Criteria Category
    		echo '&nbsp'.get_string('criteria_category','checkoutcome');
    		echo '<select name="criteria_category">';
    			echo '<option value="0" ';
    			if ($criteria_category == 0) {
    				echo 'selected';
    			}
    			echo '>'.get_string('all_categories','checkoutcome').'</option>';
    			foreach ($this->categories as $categ) {
    				if ($categ->id != 0) {
    					echo '<option value="'.$categ->id.'" ';
    					if ($criteria_category == $categ->id) {
    						echo 'selected';
    					}
    					echo '>'.$categ->name.'</option>';
    				}    				
    			}
    		echo '</select>';
    		// Criteria Display
    		echo '&nbsp'.get_string('criteria_display','checkoutcome');
    		echo '<select name="criteria_display">';
    		echo '<option value="0" ';
    			if ($criteria_display == 0) {
    				echo 'selected';
    			}
    			echo '>'.get_string('all_displays','checkoutcome').'</option>';
    		foreach ($this->displays as $disp) {
    			echo '<option value="'.$disp->id.'" ';
    					if ($criteria_display == $disp->id) {
    						echo 'selected';
    					}
    					echo '>'.$disp->name.'</option>';
    					$i++;
    		}
    		echo '</select>';
			// Criteria value type
    		echo '&nbsp'.get_string('criteria_valuetype','checkoutcome');
    		echo '<select name="criteria_valuetype">';
    			echo '<option value="0" ';
    			if ($criteria_valuetype == 0) {
    				echo 'selected';
    			}
    			echo '>'.get_string('valuetype_percent','checkoutcome').'</option>';
    			echo '<option value="1" ';
    			if ($criteria_valuetype == 1) {
    				echo 'selected';
    			}
    			echo '>'.get_string('valuetype_color','checkoutcome').'</option>';
    		echo '</select>';
    		echo '<input type="hidden" value="'.$this->checkoutcome->id.'" name="checkoutcome">';
    		echo '<input type="submit" value="'.get_string('calculate','checkoutcome').'" style="float:right;"></input>';
    	echo '</div>';
    	echo '</form>';
    	echo $OUTPUT->box_end();

    	// Select period
    	if (!$this->isDefaultPeriod()) {
    		// Display selected period name and dates, and select box
			if ($criteria_period == 0) 
			{
				echo $OUTPUT->heading(get_string('all_periods','checkoutcome'));
			}
			else
			{
				if($this->selected_period->startdate!=null && $this->selected_period->startdate!=0)
				{
					echo $OUTPUT->heading($this->selected_period->name.' ('.$this->date_fr('d M Y',$this->selected_period->startdate).' - '.$this->date_fr('d M Y',$this->selected_period->enddate).')');
				}
				else
				{
					echo $OUTPUT->heading($this->selected_period->name);
				}
			}
    	}
    	
    	
    	$selected_items = array(); 
    	if ($this->items != null) {   	
	    	foreach ($this->items as $item) {
	    		$crit1 = false;
	    		if ($criteria_category == 0 || $item[1]->category == $criteria_category) {
	    			$crit1 = true;
	    		}
	    		$crit2 = false;
	    		if ($criteria_display == 0 || $item[1]->display == $criteria_display) {
	    			$crit2 = true;
	    		} 
	    		if ($crit1 && $crit2) {
	    			$selected_items[] = $item;
	    		}
	    	}
    	}
    	
    	if (count($selected_items) != 0) {    			
	   		$this->showRatesVsOutcome($selected_items,$criteria_student,$criteria_student_group,$criteria_period,$criteria_category,$criteria_display,$criteria_valuetype);
    	} else {
    		echo get_string('no_results','checkoutcome');
    	}
  	
    }
    
    /**
     * Displays result of the calculation asked by user
     * @param Array $selected_items
     * @param Integer $crit_category
     * @param Integer $crit_display
     */
    function showRatesVsOutcome($selected_items, $criteria_student, $criteria_student_group, $criteria_period, $crit_category, $crit_display, $criteria_valuetype) {
    	global $DB,$COURSE,$OUTPUT, $CFG;
    	
    	echo '<div id="result_outcomes" class="result_outcomes">'.get_string('results_per_outcome','checkoutcome').'</div>';
    	//Get students
     	if ($CFG->version < 2011120100) {
		    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		}
		else {
		    $context = context_course::instance($COURSE->id);
		}

		if($criteria_student==0)
		{
			if ($criteria_student_group == 0) 
			{
				$query = 'select u.id as id, firstname, lastname, picture, imagealt, email from {role_assignments} as a, {user} as u, {role} as r where a.contextid=' . $context->id . ' and r.archetype="student" and a.userid=u.id and r.id=a.roleid;';
			}
			else
			{
				$query = 'select u.id as id, firstname, lastname, picture, imagealt, email from {role_assignments} as a, {user} as u, {groups_members} as g, {role} as r where a.contextid=' . $context->id . ' and r.archetype="student" and g.groupid=' . $criteria_student_group . ' and u.id=g.userid and a.userid=u.id and r.id=a.roleid;';
			}
    	}
		else
		{
			if ($criteria_student_group == 0) 
			{
				$query = 'select u.id as id, firstname, lastname, picture, imagealt, email from {role_assignments} as a, {user} as u, {role} as r where u.id='. $criteria_student .' and a.contextid=' . $context->id . ' and r.archetype="student" and a.userid=u.id and r.id=a.roleid;';
			}
			else
			{
				$query = 'select u.id as id, firstname, lastname, picture, imagealt, email from {role_assignments} as a, {user} as u, {groups_members} as g, {role} as r where u.id='. $criteria_student .' and a.contextid=' . $context->id . ' and r.archetype="student" and g.groupid=' . $criteria_student_group . ' and u.id=g.userid and a.userid=u.id and r.id=a.roleid;';
			}
		}
		$students = $DB->get_records_sql( $query );
    	$studentscount = count($students);
    	 
    	// Strings
    	$strshortname = get_string('shortname','checkoutcome');
    	$strfullname = get_string('fullname', 'checkoutcome');
    	$strcategory = get_string('category', 'checkoutcome');
    	$strdisplay = get_string('display', 'checkoutcome');
		$strcounter = get_string('counter', 'checkoutcome');
    	//$strstudentgrade = get_string('student_selection', 'checkoutcome');
    	$stranswer = get_string('answer', 'checkoutcome');
    	$strteacherrate = get_string('teacherrate', 'checkoutcome');
    	$strstudentrate = get_string('studentrate', 'checkoutcome');
    	 
    	$tableitems = array();
    	 
    	
    	// Parameters to check scale
    	$refscaleid = $selected_items[0][0]->scaleid;
    	$badScale = false;
    	   	
    	//Paramaters to calculate mean
    	$studentrates = array();
    	$teacherrates = array();
		foreach ($selected_items as $item) {
			// Getting scale items
			$scale = $DB->get_record('scale',array('id' => $item[0]->scaleid));
			$scaleitems = mb_split(",",$scale->scale);
			// preparing tables indexes
			foreach ($scaleitems as $si) {
				$studentrates[$si] = 0;
				$teacherrates[$si] = 0;
			}
		}

			
    	foreach ($selected_items as $item) {
		
			// Getting scale items
			$scale = $DB->get_record('scale',array('id' => $item[0]->scaleid));
			$scaleitems = mb_split(",",$scale->scale);

			$badScale = false;
    		/*//Check that all items use the same scale, if not no mean will be calculated    		
    		if ($item[0]->scaleid != $refscaleid){
    				$badScale = true;
    		}    		
			*/
    		// Getting category name
    		if (!$categoryname = $DB->get_field('checkoutcome_category', 'name',array('id' => $item[1]->category))) {
    			$categoryname = get_string('NA', 'checkoutcome');
    		}
    	
    		// Getting display name
    		if (!$displayname = $DB->get_field('checkoutcome_display', 'name',array('id' => $item[1]->display))) {
    			$displayname = get_string('NA', 'checkoutcome');
    		}
			
			//Getting counter
			$counter=0;
			foreach ($students as $st)
			{
				if($criteria_period==0)
				{
					foreach ($this->periods as $period) 
					{
						$gr = null;
						$gr = $DB->get_field('checkoutcome_selfgrading', 'count', array('checkoutcomeitem' => $item[1]->id, 'userid' => $st->id, 'period' => $period->id));
						if ($gr != null && $gr != 0) {
							$counter+=$gr;
						}
					}
				}
				else
				{
					$gr = null;
					$gr = $DB->get_field('checkoutcome_selfgrading', 'count', array('checkoutcomeitem' => $item[1]->id, 'userid' => $st->id, 'period' => $this->selected_period->id));
					if ($gr != null && $gr != 0) 
					{
						$counter+=$gr;
					}
				}
			}
    	
    		// Studentsitems : Calculating student's rates for each scale items
    		$selfgrades = array();
    		// preparing table indexes
    		$m = 1;
    		foreach ($scaleitems as $ssi) {
    			$selfgrades[$m] = 0;
    			$m++;
    		}
    		// Filling selfgrades table    		
    		foreach ($students as $st) {
    			$gr = null;
				$gr = $DB->get_field('checkoutcome_selfgrading', 'grade', array('checkoutcomeitem' => $item[1]->id, 'userid' => $st->id, 'period' => $this->selected_period->id));
				if ($gr != null && $gr != 0) {
    				$selfgrades[$gr]++;
    			}
    		}
    		// Fill in studentsitems
    		$studentsitems = array();
    		$sindex = 1;
    		$sumrate = 0;
    		foreach ($scaleitems as $si) {
				$studentsitem = new stdClass();
				$studentsitem->scaleitem = $si;
                if($criteria_valuetype==0){
					$studentsitem->rate = '0';
				}
				else{
					$studentsitem->rate = '<img src="pix/0.png"/>';
				}

				if($criteria_period==0)
				{
					foreach ($this->periods as $period) {
						$m = 1;
						foreach ($scaleitems as $ssi) {
							$selfgrades[$m] = 0;
							$m++;
						}
						// Filling selfgrades table    		
						foreach ($students as $st) {
							$gr = null;
							$gr = $DB->get_field('checkoutcome_selfgrading', 'grade', array('checkoutcomeitem' => $item[1]->id, 'userid' => $st->id, 'period' => $period->id));
							if ($gr != null && $gr != 0) {
								$selfgrades[$gr]++;
							}
						}
						if ($selfgrades[$sindex]) {
							$rate = number_format($selfgrades[$sindex]/$studentscount*100,0);
							if($criteria_valuetype==0)
							{
								$studentsitem->rate .= ' | '.$rate;
							}
							else
							{
								if($rate >0 AND $rate<=10)
								{
									$studentsitem->rate .= '<img src="pix/0-10.png"/>'.'.';
								}
								if($rate >10 AND $rate<=20)
								{
									$studentsitem->rate .= '<img src="pix/10-20.png"/>'.'.';
								}
								if($rate >20 AND $rate<=30)
								{
									$studentsitem->rate .= '<img src="pix/20-30.png"/>'.'.';
								}
								if($rate >30 AND $rate<=40)
								{
									$studentsitem->rate .= '<img src="pix/30-40.png"/>'.'.';
								}
								if($rate >40 AND $rate<=50)
								{
									$studentsitem->rate .= '<img src="pix/40-50.png"/>'.'.';
								}
								if($rate >50 AND $rate<=60)
								{
									$studentsitem->rate .= '<img src="pix/50-60.png"/>'.'.';
								}
								if($rate >60 AND $rate<=70)
								{
									$studentsitem->rate .= '<img src="pix/60-70.png"/>'.'.';
								}
								if($rate >70 AND $rate<=80)
								{
									$studentsitem->rate .= '<img src="pix/70-80.png"/>'.'.';
								}
								if($rate >80 AND $rate<=90)
								{
									$studentsitem->rate .= '<img src="pix/80-90.png"/>'.'.';
								}
								if($rate >90 AND $rate<100)
								{
									$studentsitem->rate .= '<img src="pix/90-100.png"/>'.'.';
								}
								if($rate==100)
								{
									$studentsitem->rate .= '<img src="pix/100.png"/>'.'.';
								}
							}
							$sumrate += $rate;
							//for later calculation of the mean
							$studentrates[$si] += $rate/100;
						} else {
						/*
							if($criteria_valuetype==0)
							{
								$studentsitem->rate .= ' | 0';
							}
							else
							{
								$studentsitem->rate .= '<img src="pix/0.png"/>'.'.';
							}
						*/
						}
					}
				}
				else
				{
					if ($selfgrades[$sindex]) {
						$rate = number_format($selfgrades[$sindex]/$studentscount*100,0);
						$studentsitem->rate = $rate;
						$sumrate += $rate;
						//for later calculation of the mean
						$studentrates[$si] += $rate/100;
					} else {
						$studentsitem->rate = 0;
					}
				}
				$studentsitems[] = $studentsitem;
				$sindex++;
    		}  		
    
   		// Teachersitems : Calculating teacher's rates for each scale items
    		$grades = array();
    		//preparing table indexes
    		$n = 1;
    		foreach ($scaleitems as $ssi) {
    			$grades[$n] = 0;
    			$n++;
    		}
    		// filling grades table    		
    		foreach ($students as $st) {
    			$gr = null;
    			$gr = $DB->get_field('checkoutcome_teachergrading', 'grade', array('checkoutcomeitem' => $item[1]->id, 'userid' => $st->id, 'period' => $this->selected_period->id));
    			if ($gr != null && $gr != 0) {				
    				$grades[$gr]++;
    			}
    		}
    		// Fill in teachersitems
    		$teachersitems = array();
    		$tindex = 1;
    		$sumrate = 0;
    		foreach ($scaleitems as $si) {
    			$teachersitem = new stdClass();
    			$teachersitem->scaleitem = $si;
				if($criteria_valuetype==0)
							{
								$teachersitem->rate = '0';
							}
				else
							{
								$teachersitem->rate = '<img src="pix/0.png"/>';
							}

				if($criteria_period==0)
				{
					foreach ($this->periods as $period) {
						$n = 1;
						foreach ($scaleitems as $ssi) {
							$grades[$n] = 0;
							$n++;
						}
						// Filling selfgrades table    		
						foreach ($students as $st) {
							$gr = null;
							$gr = $DB->get_field('checkoutcome_teachergrading', 'grade', array('checkoutcomeitem' => $item[1]->id, 'userid' => $st->id, 'period' => $period->id));
							if ($gr != null && $gr != 0) {
								$grades[$gr]++;
							}
						}
						if ($grades[$tindex]) {
							$rate = number_format($grades[$tindex]/$studentscount*100,0);
							if($criteria_valuetype==0)
							{
								$teachersitem->rate .= ' | '.$rate;
							}
							else
							{
								if($rate >0 AND $rate<=10)
								{
									$teachersitem->rate .= '<img src="pix/0-10.png"/>'.'.';
								}
								if($rate >10 AND $rate<=20)
								{
									$teachersitem->rate .= '<img src="pix/10-20.png"/>'.'.';
								}
								if($rate >20 AND $rate<=30)
								{
									$teachersitem->rate .= '<img src="pix/20-30.png"/>'.'.';
								}
								if($rate >30 AND $rate<=40)
								{
									$teachersitem->rate .= '<img src="pix/30-40.png"/>'.'.';
								}
								if($rate >40 AND $rate<=50)
								{
									$teachersitem->rate .= '<img src="pix/40-50.png"/>'.'.';
								}
								if($rate >50 AND $rate<=60)
								{
									$teachersitem->rate .= '<img src="pix/50-60.png"/>'.'.';
								}
								if($rate >60 AND $rate<=70)
								{
									$teachersitem->rate .= '<img src="pix/60-70.png"/>'.'.';
								}
								if($rate >70 AND $rate<=80)
								{
									$teachersitem->rate .= '<img src="pix/70-80.png"/>'.'.';
								}
								if($rate >80 AND $rate<=90)
								{
									$teachersitem->rate .= '<img src="pix/80-90.png"/>'.'.';
								}
								if($rate >90 AND $rate<100)
								{
									$teachersitem->rate .= '<img src="pix/90-100.png"/>'.'.';
								}
								if($rate==100)
								{
									$teachersitem->rate .= '<img src="pix/100.png"/>'.'.';
								}
							}
							$sumrate += $rate;
							//for later calculation of the mean
							$teacherrates[$si] += $rate/100;
						} else {
/*
							if($criteria_valuetype==0)
							{
								$teachersitem->rate .= ' | 0';
							}
							else
							{
								$teachersitem->rate .= '<img src="pix/0.png"/>'.'.';
							}
*/
						}
					}
				}
				else
				{
					if ($grades[$tindex]) {
						$rate = number_format($grades[$tindex]/$studentscount*100,0);
						$teachersitem->rate = $rate;
						$sumrate += $rate;
						//for later calculation of the mean
						$teacherrates[$si] += $rate/100;
					} else {
						$teachersitem->rate = 0;
					}
				}
    			$teachersitems[] = $teachersitem;
    			$tindex++;
    		}    		
    	
    		// Fill in empty table cells, determine maxrowcount
    		if ($sindex < $tindex) {
    			$maxrowcount = $tindex-1;
    			while ($tindex-$sindex > 0) {
    				$studentsitem = new stdClass();
    				$studentsitem->scaleitem = '';
    				$studentsitem->rate = '';
    				$studentsitems[] = $studentsitem;
    				$sindex++;
    			}
    		} else {
    			$maxrowcount = $sindex-1;
    			while ($sindex-$tindex > 0) {
    				$teachersitem = new stdClass();
    				$teachersitem->scaleitem = '';
    				$teachersitem->rate = '';
    				$teachersitems[] = $teachersitem;
    				$tindex++;
    			}
    		}
    	
			//$counter=12;
    		// Fill in tableitem
    		$tableitem = array();
    		// 0. max row count
    		$tableitem[] = $maxrowcount;
    		// 1. Number
    		$tableitem[] = $item[4]->shortname;
    		// 2. Name
    		$tableitem[] = $item[4]->fullname;
    		// 3. Category name
    		$tableitem[] = $categoryname;
    		// 4. Display name
    		$tableitem[] = $displayname;
			// 5. Counter
    		$tableitem[] = $counter;
    		// 6. Student's items
    		$tableitem[] = $studentsitems;
    		// 7. Teacher's items
    		$tableitem[] = $teachersitems;
    	
    		// Adding prepared tableitem to main table
    		$tableitems[] = $tableitem;
    	
    	}
    	 
    	 
    	// Table definition
		echo '<div style="text-align:center;">';
		$table = new html_table();
		$table->head  = array($strshortname, $strfullname, $strcategory, $strdisplay, $strcounter, $stranswer, $strstudentrate, $strteacherrate);
		$table->size  = array('5%', '21%', '7%', '7%', '7%', '11%', '17%', '17%');
		$table->align = array('center', 'left', 'center', 'center', 'center', 'left', 'center','center');
		$table->width = '100%';
		echo html_writer::table($table);
		
    	foreach ($tableitems as $ti) {
			$data=array();
    		for ($i = 0; $i < $ti[0]; $i++) {
    			$line = array();
    			//Number, Name, Category, Display
    			if ($i == 0) {
    				$line[] = $ti[1];
    				$line[] = $ti[2];
    				$line[] = $ti[3];
    				$line[] = $ti[4];
					$line[] = $ti[5];
    			} else {
    				$line[] = '';
    				$line[] = '';
    				$line[] = '';
    				$line[] = '';
					$line[] = '';
    			}
    	
    			//Student's items and rate
    			$line[] = $ti[6][$i]->scaleitem;
    			$line[] = $ti[6][$i]->rate;
    	
    			//Teacher's items and rate
    			//$line[] = $ti[6][$i]->scaleitem;
    			$line[] = $ti[7][$i]->rate;
    	
    			$data[] = $line;
    		}
			
			$table = new html_table();
			$table->size  = array('5%', '21%', '7%', '7%', '7%', '11%', '17%', '17%');
			$table->align = array('center', 'left', 'center', 'center', 'center', 'left', 'center','center');
			$table->width = '100%';
			$table->data  = $data;
			echo html_writer::table($table);
    	}
		echo '</div>';
		
		
    	/*
		echo '<div style="text-align:center;">';
		$table = new html_table();
		$table->head  = array($strshortname, $strfullname, $strcategory, $strdisplay, $stranswer, $strstudentrate, $strteacherrate);
		$table->size  = array('5%', '25%', '10%', '10%', '15%', '10%', '10%');
		$table->align = array('center', 'left', 'center', 'center', 'left', 'center','center');
		$table->width = '100%';
		$table->data  = $data;
		echo html_writer::table($table);
		echo '</div>';
		*/

		// Select period
		if (!$this->isDefaultPeriod()) {
    		// Display selected period name and dates, and select box
			if ($criteria_period == 0) 
			{
				echo $OUTPUT->heading(get_string('all_periods','checkoutcome'));
			}
			else
			{
				if($this->selected_period->startdate!=null && $this->selected_period->startdate!=0)
				{
					echo $OUTPUT->heading($this->selected_period->name.' ('.$this->date_fr('d M Y',$this->selected_period->startdate).' - '.$this->date_fr('d M Y',$this->selected_period->enddate).')');
				}
				else
				{
					echo $OUTPUT->heading($this->selected_period->name);
				}
			}
    	}
			
			
			if (!$badScale) {
					echo '<div id="mean_results" class="result_outcomes">'.get_string('mean_results','checkoutcome');
					echo '<br>';    		
					if ($crit_category != -1) {    			
						echo get_string('for_category','checkoutcome').'&nbsp:&nbsp';
						if ($crit_category != 0) {
							echo $this->categories[$crit_category]->name.'&nbsp';
						} else {
							echo get_string('all_categories', 'checkoutcome');
						}
					}
					echo '<br>';
					if ($crit_display != -1) {
						echo get_string('for_display','checkoutcome').'&nbsp:&nbsp';
						if ($crit_display != 0) {
							echo $this->displays[$crit_display]->name.'&nbsp';
						} else {
							echo get_string('all_displays', 'checkoutcome');
						}
					}
					echo '</div>';
			
			
			
				$scalelist=array();
			
				foreach ($selected_items as $item) {
					// Getting scale items
					$scale = $DB->get_record('scale',array('id' => $item[0]->scaleid));
					$scaleitems = mb_split(",",$scale->scale);
					if(!in_array($scaleitems, $scalelist)) {
						$scalelist[]=$scaleitems;
					}
				}
				// Table definition
				$data=array();
				foreach ($scalelist as $sl) {
					foreach ($sl as $si) {
						
							$line = array();    			
							// Answer
							$line[] = $si;
							if($criteria_period==0)
							{
								// Student mean
								$line[] = number_format(($studentrates[$si]/(count($selected_items)*count($this->periods)))*100, 2);
								// Teacher mean
								$line[] = number_format(($teacherrates[$si]/(count($selected_items)*count($this->periods)))*100, 2);
							}
							else
							{
								// Student mean
								$line[] = number_format(($studentrates[$si]/count($selected_items))*100, 2);
								// Teacher mean
								$line[] = number_format(($teacherrates[$si]/count($selected_items))*100, 2);
							}
							$data[] = $line;						
					}				
				}
					
				
				echo '<div style="text-align:center;">';
				$table = new html_table();
				$table->head  = array($stranswer, $strstudentrate, $strteacherrate);
				$table->size  = array('15%', '10%', '10%');
				$table->align = array('left', 'center','center');
				$table->width = '100%';
				$table->data  = $data;
				echo html_writer::table($table);
				echo '</div>';
    		
			} else {
				echo get_string('mean_calculation_impossible','checkoutcome');
			}  
		
    }  
    
    /**
     * Displays list_display screen
     */
    function view_list_disp() {
    	global $DB,$OUTPUT;
    	$thispage = new moodle_url('/mod/checkoutcome/list_disp.php');
    	$update_disp_page = new moodle_url('/mod/checkoutcome/update_disp.php');
    	$displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $this->checkoutcome->id),'id');
    	
    	$strdisplayname = get_string('display','checkoutcome');
    	$strdisplaydescription = get_string('display_description','checkoutcome');
    	$stredit = get_string('edit_display','checkoutcome');
    	
    	echo '<fieldset class="clearfix">';
    	if ($displays) {
    		$data = array();
	    	foreach ($displays as $disp) {
	    		$line = array();
	    		if ($disp->iswhitefont) {
	    			$fontcolor = '#fff';
	    		} else {
	    			$fontcolor = '#000';
	    		}
	    		// Display name
	    		$line[] = '<span style="background-color:#'.$disp->color.';color:'.$fontcolor.'">'.$disp->name.'</span>';
	    		// Display description
	    		$line[] = '<span style="background-color:#'.$disp->color.';color:'.$fontcolor.'">'.$disp->description.'</span>';
	    		// Edition links
	    		$title = '"'.get_string('edit_display','checkoutcome').'"';
	    		$links = '<a href="'.$update_disp_page->out(true, array('checkoutcome'=>$this->checkoutcome->id,'displayid'=>$disp->id)).'">'
	    					.'<img src="'.$OUTPUT->pix_url('/t/edit').'"  alt='.$title.' title='.$title.' />'
	    					.'</a>';
	    		// Deletion link
	    		if ($this->displayInUse($disp->id) == 0) {
	    			$title = '"'.get_string('delete_display','checkoutcome').'"';
	    			$links .= '&nbsp<a href="'.$thispage->out(true, array('action'=>'deleteDisplay','checkoutcome'=>$this->checkoutcome->id,'displayid'=>$disp->id)).'" onClick="return confirm(\''.get_string('delete_display_confirm','checkoutcome').'\')">'
	    				.'<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' />'
	    				.'</a>';
	    		}
	    		$line[] = $links;
	    		$data[] = $line;
	    	}
	    	
	    	$table = new html_table();
	    	$table->head  = array($strdisplayname, $strdisplaydescription, $stredit);
	    	$table->size  = array('20%', '30%', '5%');
	    	$table->align = array('left', 'left', 'center');
	    	$table->width = '100%';
	    	$table->data  = $data;
	    	echo html_writer::table($table);
	    	
    	} else {
    		echo get_string('empty_display_list','checkoutcome');
    	}
    	
    	
    	
    	echo '</fieldset>';
    	echo '<form id="add_new" action="'.$update_disp_page->out_omit_querystring().'">';
    	echo '<input type="hidden" value="'.$this->cm->id.'" name="id">';
    	echo '<input type="submit" value="'.get_string('add_display','checkoutcome').'"></input>';
    	echo '</form>';
    	 
    }
    
    /**
     * Displays list_periods screen
     */
    function view_list_period() {
    	global $DB,$OUTPUT,$PAGE;
    	$thispage = new moodle_url('/mod/checkoutcome/list_period.php');
    	$update_period_page = new moodle_url('/mod/checkoutcome/update_period.php');
    	$periods = $DB->get_records('checkoutcome_periods',array('checkoutcome' => $this->checkoutcome->id),'shortname');
    	
		$strperiodshortname = get_string('shortname','checkoutcome');
    	$strperiodname = get_string('period','checkoutcome');
		$strlock = get_string('lock','checkoutcome');
    	$strperioddescription = get_string('period_description','checkoutcome');
    	$stredit = get_string('edit_period','checkoutcome');
    	$strperiodstart = get_string('start_date','checkoutcome');
    	$strperiodend = get_string('end_date','checkoutcome');
    	 
    	echo '<fieldset class="clearfix">';
    	if ($periods) {
			echo '<form method="post" class="mform" action="'.$thispage->out(true,null).'">';
    		$data = array();
    		foreach ($periods as $period) {
    			$line = array();
    			
				// Checkbox
	    		$line[] = '<input type="checkbox" name="periods[]" value="'.$period->id.'"/>';
				// Period shortname
    			$line[] = '<span>'.$period->shortname.'</span>';
    			// Period name
    			$line[] = '<span>'.$period->name.'</span>';
				// Period lock
				if($period->lockperiod==0)
				{
					$line[] = '<span>'.'Non'.'</span>';
				}
    			if($period->lockperiod==1)
				{
					$line[] = '<span>'.'Oui'.'</span>';
				}
    			// Period description
    			$line[] = '<span>'.$period->description.'</span>';
    			// Period start date
    			if ($period->startdate == 0) {
    				$line[] = '<span>-</span>';
    			} else {
    				$line[] = '<span>'.$this->date_fr('d M Y', $period->startdate).'</span>';
    			}    			
    			// Period enddate
    			if ($period->enddate == 0) {
    				$line[] = '<span>-</span>';
    			} else {
    				$line[] = '<span>'.$this->date_fr('d M Y', $period->enddate).'</span>';
    			}
    			// Edition links
    			$title = '"'.get_string('edit_period','checkoutcome').'"';
    			$links = '<a href="'.$update_period_page->out(true, array('checkoutcome'=>$this->checkoutcome->id,'periodid'=>$period->id)).'">'
    			.'<img src="'.$OUTPUT->pix_url('/t/edit').'"  alt='.$title.' title='.$title.' />'
    			.'</a>';
    			// Deletion link
    			if (count($this->periods) > 1 && !$this->periodInUse($period->id)) {
    				$title = '"'.get_string('delete_period','checkoutcome').'"';
    				$links .= '&nbsp<a href="'.$thispage->out(true, array('action'=>'deletePeriod','checkoutcome'=>$this->checkoutcome->id,'periodid'=>$period->id)).'" onClick="return confirm(\''.get_string('delete_period_confirm','checkoutcome').'\')">'
    				.'<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' />'
    				.'</a>';
    			}
    			$line[] = $links;
    			$data[] = $line;
    		}
    
    		$table = new html_table();
    		$table->head  = array('',$strperiodshortname, $strperiodname, $strlock, $strperioddescription, $strperiodstart, $strperiodend, $stredit);
    		$table->size  = array('5%', '10%', '10%', '10%', '30%', '10%', '10%', '5%');
    		$table->align = array('center','center', 'left','center', 'left', 'center', 'center', 'center');
    		$table->width = '100%';
    		$table->data  = $data;
    		echo html_writer::table($table);
			
			// Select lock and mark period
	    	echo '<div class="generalbox nopaddingbottom">';
		    	// Select lock
		    	echo get_string('lock','checkoutcome'). ' : ' .
		    	'<select id="lock" name="lock">'.
				'<option value="-1">'.get_string('NA','checkoutcome').'</option>'.
		    	'<option value="0">'.get_string('no','checkoutcome').'</option>'.
		    	'<option value="1">'.get_string('yes','checkoutcome').'</option>';
		    	echo '</select><br><br>';
		    	 
		    	echo '<input id="form_action" type="hidden" value="updateperiods" name="action">';
		    	echo '<input type="hidden" value="'.$this->cm->id.'" name="id">';
		    	echo '<input type="submit" name="submitperiods" class="save_grades" title="'.get_string('update_periods_desc','checkoutcome').'" value="'.get_string('update_periods','checkoutcome').'" onClick="document.getElementById(\'form_action\').value=\'updateperiods\';"/>';
		    echo '</div>';
			echo '</form>';
    
    	} else {
    		echo get_string('empty_period_list','checkoutcome');
    	}
    	 
    	 
    	echo '</fieldset>';
    	if (!$this->isDefaultPeriod() || count($this->periods) > 1) {
    		echo '<form id="add_new" action="'.$update_period_page->out_omit_querystring().'">';
    		echo '<input type="hidden" value="'.$this->cm->id.'" name="id">';
    		echo '<input type="submit" value="'.get_string('add_period','checkoutcome').'"></input>';
    		echo '</form>';
    	}
    	 	
    	
   }
    
    /**
     * Displays list_gradings screen
     */
    function view_list_gradings() {
    	global $DB,$OUTPUT,$COURSE, $CFG;
    	 
    	// Urls
    	$thispage = new moodle_url('/mod/checkoutcome/list_gradings.php');
    	$viewpage = new moodle_url('/mod/checkoutcome/view.php');
    	$periodgoalspage = new moodle_url('/mod/checkoutcome/periodgoals.php');
    	 
    	// Strings
    	$strname = get_string('name','checkoutcome');
    	$strmail = get_string('email', 'checkoutcome');
    	$strstudentrate = get_string('student_rate', 'checkoutcome');
    	$strstudentlast = get_string('student_last', 'checkoutcome');
    	$strteacherrate = get_string('teacher_rate', 'checkoutcome');
    	$strteacherlast = get_string('teacher_last', 'checkoutcome');
    	$strstatus = get_string('status', 'checkoutcome');
    	//$strperiodgoals = get_string('period_goals','checkoutcome');
    	
    	// Build students list depending on group mode
    	$students = array();    	
    	if ($this->groupmode != NOGROUPS) {
	    	// Check the user's rights an get corresponding groups
	    	$groups = array();    	    	
	    	if ($this->canviewallgroups() || $this->groupmode == VISIBLEGROUPS) {
	    		if ($this->currentgroup) {
	    			$groups[] = groups_get_group($this->currentgroup);
	    		} else {    			
	    				$groups = $this->groups;   	 			    				
	    		}    		
	    	} else {
	    		$usergroups = groups_get_user_groups($this->course->id);
	       		foreach ($usergroups[0] as $usergroupid) {
	       			if 	(array_key_exists($usergroupid, $this->groups)) {      				
	    				$groups[] = groups_get_group($usergroupid);
	       			}
	    		}
	    	}    	
	    	
	    	foreach ($groups as $group) {
	    		$members = groups_get_members($group->id);    		
	    		foreach ($members as $member) {
	    			if (user_has_role_assignment($member->id, 5) && !array_key_exists($member->id,$students)) {
	    				$students[$member->id] = $member;
	    			}
	    		}
	    	}
    	} else {
    		// Get all students of the course
	     	if ($CFG->version < 2011120100) {
			    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
			}
			else {
			    $context = context_course::instance($COURSE->id);
			}

    		$query = 'select u.id as id, firstname, lastname, picture, imagealt, email from {role_assignments} as a, {user} as u, {role} as r where a.contextid=' . $context->id . ' and r.archetype="student" and r.id=a.roleid and a.userid=u.id order by u.lastname asc;';
    		$students = $DB->get_recordset_sql( $query );
    	}
    	
    	// Select period
    	if (!$this->isDefaultPeriod()) {
    		// Display selected period name and dates, and select box
    		$options = array();
    		foreach ($this->periods as $period) {
    			$options[$period->id] = $period->name;
    		}
    		echo '<div class="title_block">';
	    		echo $OUTPUT->single_select(new moodle_url('/mod/checkoutcome/list_gradings.php', array('id' => $this->cm->id, 'studentid' => $this->studentid)), 'selected_periodid', $options, $this->selected_period->id);
	    		 
	    		echo '<div class="display_period" title="'.$this->selected_period->description.'">';
					if($this->selected_period->startdate!=null && $this->selected_period->startdate!=0)
					{
						echo $OUTPUT->heading($this->selected_period->name.' ('.$this->date_fr('d M Y',$this->selected_period->startdate).' - '.$this->date_fr('d M Y',$this->selected_period->enddate).')');
					}
					else
					{
						echo $OUTPUT->heading($this->selected_period->name);
					}    		 		
		    		echo $OUTPUT->heading($this->selected_period->description, 4);
	    		echo '</div>';
    		echo '</div>';
    		
    		
    	}
		
		// Table definition
    	$data=array();
    	if (!empty($students)){
    		foreach ($students as $student) {
    			$line=array();
    			 
    			// First Name / Surname
    			$line[] = $student->firstname.' / '.$student->lastname;
    				
    			// Email
    			$line[] = $student->email;
    			 
    			// Students' self gradings
    			$sql = 'select sg.id,sg.timemodified,sg.grade from {checkoutcome_item} as ci, {checkoutcome_selfgrading} as sg
    			where ci.checkoutcome=? and sg.period=? and sg.userid=? and sg.checkoutcomeitem=ci.id order by sg.timemodified desc';
    			
    			if ($self_gradings = $DB->get_records_sql($sql, array($this->checkoutcome->id, $this->selected_period->id, $student->id))) {
    				
    				// Student self grades count
    				$count_selfgrades = 0;    				 
    				$sg_last = 0;
    				foreach ($self_gradings as $sg) {
    					// Student Last Modified    					
    					if ($sg->timemodified && $sg->timemodified > $sg_last) {
	    					$sg_last = $sg->timemodified;	    					
    					} 
    					if ($sg->grade != null) {
    						$count_selfgrades++;
    					}    					
    				}
    				
    				// Student Rate
    				$line[] = number_format($count_selfgrades/count($this->items)*100,0);
    				// Student Last Modified
    				if ($sg_last == 0) {
    					$line[] = 'NA';
    				} else {
    					$line[] = $this->date_fr('d M Y H:i:s', $sg_last);
    				}    			
    				    				
    			} else {
    				$line[] = '0';
    				$line[] = 'NA';
    			}    			
    			
    			// Teachers' gradings
    			$t_rate = null;
    			$sql = 'select tg.id,tg.timemodified,tg.grade from {checkoutcome_item} as ci, {checkoutcome_teachergrading} as tg
    			where ci.checkoutcome=? and tg.period=? and tg.userid=? and ci.id=tg.checkoutcomeitem order by tg.timemodified desc';
    			
    			if ($teacher_gradings = $DB->get_records_sql($sql, array($this->checkoutcome->id, $this->selected_period->id, $student->id))) {
    				
    				// Grades count
    				$count_grades = 0;
    				$tg_last = 0;
    				foreach ($teacher_gradings as $tg) {
    					// Teacher Last Modified    					
    					if($tg->timemodified && $tg->timemodified > $tg_last) {
    						$tg_last = $tg->timemodified;
    					} 
    					if ($tg->grade != null) {
    						$count_grades++;
    					}
    				    					   					
    				}    				
    				// Teacher Rate
    				$line[] = $t_rate = number_format($count_grades/count($this->items)*100,0);
    				// Teacher Last Modified
    				if ($tg_last == 0) {
    					$line[] = 'NA';
    				} else {
    					$line[] = $this->date_fr('d M Y H:i:s', $tg_last);
    				}    				
    				
    			} else {
    				$line[] = '0';
    				$line[] = 'NA';
    			} 
    			
    			//Status
    			$action = get_string('action_grade','checkoutcome');
    			if ($t_rate != 0) {
    				$action = get_string('action_update','checkoutcome');
    			}
    			$line[] = '<a href="'.$viewpage->out(true, array('checkoutcome'=>$this->checkoutcome->id,'studentid' => $student->id, 'group' => $this->currentgroup, 'selected_periodid' => $this->selected_period->id)).'">'.$action.'</a>';    			    			 
    			
    			//Goals of the period    			
//     			if (has_capability('moodle/course:manageactivities', $this->context)) {
//     				$action = get_string('period_goals_edit','checkoutcome');
//     			} else {
//     				$action = get_string('period_goals_view','checkoutcome');
//     			} 
//     			//Get goal id if existing
//     			$goalid = $DB->get_field('checkoutcome_period_goals', 'id', array('userid'=>$student->id, 'period'=>$this->selected_period->id));  			    			    			
//     			$line[] = '<a href="'.$periodgoalspage->out(true, array('checkoutcome'=>$this->checkoutcome->id,'studentid' => $student->id, 'group' => $this->currentgroup, 'selected_periodid' => $this->selected_period->id, 'goalid'=>$goalid)).'">'.$action.'</a>';
    			
    			//Add line to table datas
    			$data[] = $line;
     
    		}
    	
    		$table = new html_table();
    		$table->head  = array($strname, $strmail, $strstudentrate, $strstudentlast, $strteacherrate, $strteacherlast,$strstatus);
    		$table->size  = array('10%', '10%', '5%', '15%', '5%', '15%','10%');
    		$table->align = array('center', 'center', 'center', 'center', 'center','center','center');
    		$table->width = '100%';
    		$table->data  = $data;
    		echo html_writer::table($table);
    	} else {
    		echo get_string('empty_student_list','checkoutcome').'<br/><br/>';
    	}
    	
    }

    /**
     * Displays update_category screen
     */
    function view_update_cat() {
    	global $DB;
    	global $CFG;
    	global $OUTPUT;
    	global $PAGE;
    	 
    	$document = NULL;
    	$categoryid  = optional_param('categoryid', 0, PARAM_INT);  // category ID

     	if ($CFG->version < 2011120100) {
		    $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
		}
		else {
		    $context = context_module::instance($this->cm->id);
		}

    	$thispage = new moodle_url('/mod/checkoutcome/update_cat.php', array('id' => $this->cm->id) );
    	$returl = new moodle_url('/mod/checkoutcome/list_cat.php', array('id' => $this->cm->id));
    	 
    	$currenttab = 'list_cat';
    	 
      	$category = NULL;
    	if ($categoryid) {
    		$category = $DB->get_record('checkoutcome_category', array("id"=>$categoryid));
    	}
    	 
    	 
    	$mform = new mod_checkoutcome_category_form(null,
    			array('checkoutcome'=>$this->checkoutcome->id,
    					'contextid'=>$context->id,
    					'category'=>$category,
    					'msg' => get_string('input_name_category', 'checkoutcome')));
    	 
    	if ($mform->is_cancelled()) {
    		redirect($returl);
    	} else if ($mform->get_data()) {
    		checkoutcome_edit_category($mform, $this->checkoutcome->id);
    		die();
    	}
    	 
    	$this->view_header();
    
    	echo '<div align="center"><h3>'.get_string('edit_category', 'checkoutcome').'</h3></div>'."\n";

    	$mform->display();

    	$this->view_footer();
    }
    
    /**
     * Displays update_display screen
     */
    function view_update_disp() {
    	global $DB,$CFG,$OUTPUT,$PAGE;
    
    	$document = NULL;
    	$displayid  = optional_param('displayid', 0, PARAM_INT);  // category ID

    	if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $context = context_module::instance($this->cm->id);
        }


    	$thispage = new moodle_url('/mod/checkoutcome/update_disp.php', array('id' => $this->cm->id) );
    	$returl = new moodle_url('/mod/checkoutcome/list_disp.php', array('id' => $this->cm->id));
    
    	$currenttab = 'list_disp';
    
      	$display = NULL;
      	$displaycolor = NULL;
    	if ($displayid) {
    		$display = $DB->get_record('checkoutcome_display', array("id"=>$displayid));
    		$displaycolor = $this->convertHexToRGB($display->color);
    	}
    	
    	//load JS
    	if ($CFG->version < 2012120300) // < Moodle 2.4
		{ 
			$jsmodule = array(
    			'name' => 'mod_checkoutcome',
    			'fullpath' => new moodle_url('/mod/checkoutcome/checkoutcome.js')
			);
			$PAGE->requires->yui2_lib('dom');
			$PAGE->requires->yui2_lib('event');
			$PAGE->requires->yui2_lib('connection');
			$PAGE->requires->yui2_lib('yahoo');
			$PAGE->requires->yui2_lib('element');
			$PAGE->requires->yui2_lib('slider');
			$PAGE->requires->yui2_lib('draganddrop');
			
			$serverurl = new moodle_url('/mod/checkoutcome');
			$PAGE->requires->js_init_call('M.mod_checkoutcome.init_view_display', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id, $displaycolor), true, $jsmodule);
		}
		else
		{
			$jsmodule = array(
    			'name' => 'mod_checkoutcome',
    			'fullpath' => new moodle_url('/mod/checkoutcome/checkoutcome24.js')
			);
			
			$serverurl = new moodle_url('/mod/checkoutcome');
			$PAGE->requires->js_init_call('M.mod_checkoutcome.init', array($serverurl->out(), sesskey(), $this->cm->id, $this->checkoutcome->id, '', '', '', '', '', '', $displaycolor, 'init_view_display'), true, $jsmodule);
		}		
		
    	
    
    
    	$mform = new mod_checkoutcome_display_form(null,
    			array('checkoutcome'=>$this->checkoutcome->id,
    					'contextid'=>$context->id,
    					'display'=>$display,
    					'msg' => get_string('input_name_display', 'checkoutcome')));
    
    	if ($mform->is_cancelled()) {
    		redirect($returl);
    	} else if ($mform->get_data()) {
    		checkoutcome_edit_display($mform, $this->checkoutcome->id);
    		die();
    	}
    
    	$this->view_header();
    
    	echo '<div align="center"><h3>'.get_string('edit_display', 'checkoutcome').'</h3></div>'."\n";
    	    	
    	$mform->display();
    	    	
    	$this->view_footer();    
  	}
  	
  	/**
  	 * Displays update_period screen
  	 */
  	function view_update_period() {
  		global $DB,$CFG,$OUTPUT,$PAGE;
  	
  		$periodid  = optional_param('periodid', 0, PARAM_INT);  // period ID
  	
    	if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $context = context_module::instance($this->cm->id);
        }

  		$thispage = new moodle_url('/mod/checkoutcome/update_period.php', array('id' => $this->cm->id) );
  		$returl = new moodle_url('/mod/checkoutcome/list_period.php', array('id' => $this->cm->id));

  		$currenttab = 'list_period';
  	
  		$period=null;
  		if ($periodid) {
  			$period = $DB->get_record('checkoutcome_periods', array("id"=>$periodid));  			
  		}
  		 
  		$mform = new mod_checkoutcome_period_form(null,
  				array('checkoutcome'=>$this->checkoutcome->id,
  						'contextid'=>$context->id,
  						'period'=>$period,
  						'msg' => get_string('input_name_period', 'checkoutcome')));
  	
  		if ($mform->is_cancelled()) {
  			redirect($returl);
  		} else if ($mform->get_data()) {
  			checkoutcome_edit_period($mform, $this->checkoutcome->id);
  			die();
  		}
  	
  		$this->view_header();
  	
  		echo '<div align="center"><h3>'.get_string('edit_period', 'checkoutcome').'</h3></div>'."\n";
  	
  		$mform->display();
  	
  		$this->view_footer();		
  		
  	}
  	
	//Convert date in French
	function date_fr($format, $timestamp=false) 
	{
	    if ( !$timestamp ) $date_en = date($format);
	    else               $date_en = date($format,$timestamp);
	 
	    $texte_en = array(
	        "Monday", "Tuesday", "Wednesday", "Thursday",
	        "Friday", "Saturday", "Sunday", "January",
	        "February", "March", "April", "May",
	        "June", "July", "August", "September",
	        "October", "November", "December"
	    );
	    $texte_fr = array(
	        "Lundi", "Mardi", "Mercredi", "Jeudi",
	        "Vendredi", "Samedi", "Dimanche", "Janvier",
	        "F&eacute;vrier", "Mars", "Avril", "Mai",
	        "Juin", "Juillet", "Ao&ucirc;t", "Septembre",
	        "Octobre", "Novembre", "D&eacute;cembre"
	    );
		$date_fr = $date_en;
		if(get_string('language','checkoutcome')=='Fr')
		{
			$date_fr = str_replace($texte_en, $texte_fr, $date_en);
		}
	 
	    $texte_en = array(
	        "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun",
	        "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul",
	        "Aug", "Sep", "Oct", "Nov", "Dec"
	    );
	    $texte_fr = array(
	        "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim",
	        "Jan", "F&eacute;v", "Mar", "Avr", "Mai", "Jui",
	        "Jui", "Ao&ucirc;", "Sep", "Oct", "Nov", "D&eacute;c"
	    );
		if(get_string('language','checkoutcome')=='Fr')
		{
			$date_fr = str_replace($texte_en, $texte_fr, $date_fr);
		}
	 
	    return $date_fr;
	}
  	/**
  	 * Converts Hexadecimal color code to array (R,G,B)
  	 * @param String $color
  	 * @return multitype:NULL
  	 */
  	function convertHexToRGB($color) {  		
  			$hex_R = substr($color,0,2);
  			$hex_G = substr($color,2,2);
  			$hex_B = substr($color,4,2);
  			$RGB = array();
  			$RGB[] = hexdec($hex_R);
  			$RGB[] = hexdec($hex_G);
  			$RGB[] = hexdec($hex_B);
  			return $RGB;  		
  	}
    
  	/**
  	 * Displays add_document screen
  	 */
    function view_add_document() {
    	global $DB;
    	global $CFG;
    	global $OUTPUT;
    	global $PAGE;
    
    	$document = NULL;
    	$sg_itemid  = optional_param('itemid', 0, PARAM_INT);  // selfgrading item ID
    	$ch_itemid  = optional_param('chitemid', 0, PARAM_INT);  // checkoutcome item ID
    	$documentid  = optional_param('documentid', 0, PARAM_INT); // document ID
    	
    	if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $context = context_module::instance($this->cm->id);
        }

    	$thispage = new moodle_url('/mod/checkoutcome/add_document.php', array('id' => $this->cm->id, 'selected_periodid' =>$this->selected_period->id));
    	$returl = new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id, 'selected_periodid' =>$this->selected_period->id));
    	if ($ch_itemid) {
    		$returl->set_anchor('ch_item_'.$ch_itemid);
    	}
    
    	$currenttab = 'view';
    
      	$document = NULL;
     	if ($documentid) {
     		$document = $DB->get_record('checkoutcome_document', array('id'=>$documentid));
     	}
    
    	$options = array('subdirs'=>0, 'maxbytes'=>get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes), 'maxfiles'=>1, 'accepted_types'=>'*', 'return_types'=>FILE_INTERNAL);
    
    	$mform = new mod_checkoutcome_document_form(null,
    			array('checkoutcome' => $this->checkoutcome->id,
    					'gradeid' => $sg_itemid,
    					'chitemid' => $ch_itemid,
    					'periodid' => $this->selected_period->id,
    					'contextid' => $context->id,
    					'filearea'=>'document',
    					'document' => $document, 
    					'msg' => get_string('input_name_document', 'checkoutcome'),
    					'options'=>$options));
    
    	if ($mform->is_cancelled()) {
    		redirect($returl);
    	} else if ($mform->get_data()) {
    		checkoutcome_edit_document($mform, $this->checkoutcome->id, $ch_itemid, $this->selected_period->id);
    		die();
    	}
    
    	$this->view_header();
    
    	echo '<div align="center"><h3>'.get_string('add_document', 'checkoutcome').'</h3></div>'."\n";

    	$mform->display();
    
    	$this->view_footer();
    }
    
    /**
     * Displays edit_outcome screen
     */
    function view_edit_outcome() {
    	global $DB;
    	global $CFG;
    	global $OUTPUT;
    	global $PAGE;
    
    	$document = NULL;
    	$gradeitemid  = optional_param('gradeitem', 0, PARAM_INT);  // gradeitem ID
    	$checkoutcomeitemid  = optional_param('checkoutcomeitem', 0, PARAM_INT);  // checkoutcomeitem ID
    	$inuse = optional_param('inuse', 0, PARAM_INT);  // in use count
    	if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $context = context_module::instance($this->cm->id);
        }

    
    	$thispage = new moodle_url('/mod/checkoutcome/edit_outcome.php', array('id' => $this->cm->id) );
    	$returl = new moodle_url('/mod/checkoutcome/edit.php', array('id' => $this->cm->id));
    
    	$currenttab = 'edit';    
 
    	$gradeitem = NULL;
    	if ($gradeitemid) {
    		$gradeitem = $DB->get_record('grade_items', array("id"=>$gradeitemid));
    	}
    	
    	$checkoutcomeitem = NULL;
    	if ($checkoutcomeitemid) {
    		$checkoutcomeitem = $DB->get_record('checkoutcome_item', array("id"=>$checkoutcomeitemid));
    	}
    
    
    	$mform = new mod_checkoutcome_editoutcome_form(null,
    			array('checkoutcome'=>$this->checkoutcome->id,
    					'courseid' =>$this->course->id,
    					'contextid'=>$context->id,
    					'gradeitem'=>$gradeitem,
    					'checkoutcomeitem'=>$checkoutcomeitem,
    					'inuse'=>$inuse,
    					'msg' => get_string('outcome', 'checkoutcome')));
    
    	if ($mform->is_cancelled()) {
    		redirect($returl);
    	} else if ($mform->get_data()) {
    		checkoutcome_edit_outcome($mform, $this->checkoutcome->id);
    		die();
    	}
    
    	$this->view_header();
    
    	echo '<div align="center"><h3>'.get_string('edit_outcome', 'checkoutcome').'</h3></div>'."\n";

    	$mform->display();
    
	   	$this->view_footer();
    }
    
    /**
     * Displays add_outcome screen
     */
    function view_add_outcome() {
    	global $OUTPUT, $DB, $PAGE;
    	
    	$edit_page = new moodle_url('/mod/checkoutcome/edit.php');
		// DEBUG
		//echo "<br />DEBUG :: locallib.php :: 3471 :: view_add_outcome()<br />COURSE : ".$this->course->id."\n";

    	// Getting the list of outcomes for this course
    	//$outcomes = $DB->get_records('grade_outcomes',array('courseid' => $this->course->id),'shortname');
        $outcomes = null;
		$outcomes_course = $DB->get_records('grade_outcomes_courses',array('courseid' => $this->course->id));
		if ($outcomes_course){
			$params=array();
			$where='';
			foreach($outcomes_course as $outcome_course){
				$params[]=$outcome_course->outcomeid;
				if (empty($where)){
					$where .= ' (id=?) ';
				}
				else{
                    $where .= ' OR (id=?) ';
				}
			}

            $sql="SELECT * FROM {grade_outcomes} WHERE ".$where." ORDER BY id";

			// DEBUG
			//echo "<br />DEBUG :: locallib.php :: 3493 :: view_add_outcome()<br />SQL : $sql<br />\n";
			//print_object($params);
            //exit;
			$outcomes=$DB->get_records_sql($sql, $params);
		}

		// DEBUG
		//echo "<br />DEBUG :: locallib.php :: 3474 :: view_add_outcome()<br />\n";
		//print_object($outcomes);
		//exit;
    	
   		$this->view_header();

   		$jsmodule = array(
   			'name'     => 'mod_checkoutcome',
   			'fullpath' => '/mod/checkoutcome/checkoutcome.js'
   		);
    	
   		// Prepare default values
    		
   		// Default category
    	$defaults = get_string('select_default_category','checkoutcome').
    	'<select id="default_category" name="default_categ">'.
    	'<option value="NA">'.get_string('NA','checkoutcome').'</option>';
   		$categories = $DB->get_records('checkoutcome_category',array('checkoutcome' => $this->checkoutcome->id),'id');
   		foreach ($categories as $categ) {
   			$defaults.='<option value="'.$categ->id.'">'.$categ->name.'</option>';
    	}
   		$defaults.='</select>&nbsp';
    			
   		// Default Display
    	$defaults .= get_string('select_default_display','checkoutcome').
    	'<select id="default_display" name="default_display">'.
    	'<option value="NA">'.get_string('NA','checkoutcome').'</option>';
   		$displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $this->checkoutcome->id),'id');
   		foreach ($displays as $disp) {
   			$defaults.='<option value="'.$disp->id.'">'.$disp->name.'</option>';
    	}
   		$defaults.='</select><br/>';
   		// Teacher scale
    	//$scales = $DB->get_records('scale',array('courseid' => $this->course->id),'id');
    	'<br/>';
    	// End of Defaults
    	
    	// Javascript init
   		$PAGE->requires->js_init_call('M.mod_checkoutcome.init_defaults', null, true ,$jsmodule);
    		
		// Starting Page
	   	echo '<div align="center"><h3>'.get_string('add_outcome', 'checkoutcome').'</h3></div>'."\n";
     	echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter nopaddingbottom');
		echo '<div id="div_check_uncheck"><input type="checkbox" id="check_uncheck_all" onClick="javascript:M.mod_checkoutcome.check();">&nbsp'.get_string('check_uncheck_all','checkoutcome').'</div>';
	   	echo '<form id="addOutcomesForm" action="'.$edit_page->out_omit_querystring().'">';
    	echo '<ul style="list-style-type:none;">';
    	$i = 0;
		if (!empty($outcomes)){
    		foreach ($outcomes as $outcome) {
    			if(!$gitem = $DB->get_record('grade_items',array('courseid' => $this->course->id,'outcomeid' => $outcome->id,'itemmodule' => $this->cm->modname, 'iteminstance' => $this->cm->instance))) {
	    			echo '<li ><input type="checkbox" value="'.$outcome->id.'" name="out'.$i.'"/>&nbsp'.$outcome->fullname.'</li>';
    				$i++;
    			} else {
    				echo '<li class="checkbox_disabled"><input disabled="true" type="checkbox" />&nbsp'.$outcome->fullname.'</li>';
	    		}
            }
    		echo '</ul>';
    	}
    	echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter nopaddingbottom');
    	echo $defaults;
   		echo '<br/><br/>';
   		echo '<div id="define_defaults">';
   		echo '</div>';
	  	// Action buttons
   		echo '<input id="actionaddOutcome" type="hidden" name="action" value="addOutcome"/>';
    	echo '<input id="checkoutcomeid" type="hidden" name="checkoutcome" value="'.$this->checkoutcome->id.'"/>';
   		echo '<input type="submit" name="addOutcomes" value="'.get_string('add_outcome','checkoutcome').'" onClick="javascript:document.getElementById(\'actionaddOutcome\').value=\'addOutcome\';"/>';
   		echo '&nbsp';
    	echo '<input type="submit" name="cancelOutcomes" value="'.get_string('cancel','checkoutcome').'" onClick="javascript:M.mod_checkoutcome.uncheckall();document.getElementById(\'actionaddOutcome\').value=\'cancelOutcome\';"/>';
   		echo '</form>';
   		echo $OUTPUT->box_end();
    	echo $OUTPUT->box_end();
   		$this->view_footer();
	}

    /**
     * Displays period goals screen
     */
    function view_periodgoals() {    	
    	global $DB,$OUTPUT, $CFG;
    	
    	$goalid  = optional_param('goalid', 0, PARAM_INT);
    	
    	if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $context = context_module::instance($this->cm->id);
        }

    	$student = null;
    	$retview = null;
		
  		if ($this->studentid){
  			$student = $DB->get_record('user', array('id' => $this->studentid));
  			$retview = new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id, 'group' => $this->currentgroup,'selected_periodid' => $this->selected_period->id, 'studentid' => $student->id));
  		} else {
  			$student = $DB->get_record('user', array('id' => $this->userid));
  			$retview = new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id, 'selected_periodid' => $this->selected_period->id));  			
  		}
  		
  		$goal=null;
  		if ($goalid) {
  			$goal = $DB->get_record('checkoutcome_period_goals', array('id' => $goalid));  			
  		}
  		 
    	//if (has_capability('moodle/course:manageactivities', $this->context)) {
		if ($this->isGradingByTeacher()) {
	    	$mform = new mod_checkoutcome_goal_form(null,
	  				array('checkoutcome'=>$this->checkoutcome->id,
	  						'contextid'=>$context->id,
	  						'goal'=>$goal,
	  						'studentid'=>$student->id,
	  						'periodid'=>$this->selected_period->id,
	  						'msg' => get_string('period_goals', 'checkoutcome')));
	  	
	  		if ($mform->is_cancelled()) {
	  			redirect($retview);
	  		} else if ($mform->get_data()) {
	  			checkoutcome_edit_goal($mform, $this->checkoutcome->id, $this->currentgroup);
	  			die();
	  		}  	
    	}

    	// Print page
    	
    	// Header
    	$this->view_header();
  	
  		// Display module title or student name if teacher is grading  		
  		echo '<a class="backlink" href="'.$retview.'" class="backlink">'.get_string('back','checkoutcome').'</a>';
  		    	
  		echo $OUTPUT->heading($student->firstname.' '.$student->lastname.' ('.$student->username.')',1);
  				
  		// Display period infos
  		if (!$this->isDefaultPeriod()) {
  			// Display selected period name and dates, and select box
			if($this->selected_period->startdate!=null && $this->selected_period->startdate!=0)
			{
				echo $OUTPUT->heading($this->selected_period->name.' ('.$this->date_fr('d M Y',$this->selected_period->startdate).' - '.$this->date_fr('d M Y',$this->selected_period->enddate).')'); 
			}
			else
			{
				echo $OUTPUT->heading($this->selected_period->name);
			} 			
  		}
  		
  		//if (has_capability('moodle/course:manageactivities', $this->context)) {
		if ($this->isGradingByTeacher()) {
  			// Display form
  			$mform->display();
  		} else {
//   			if ($goal != null) {
//   				$teacher = $DB->get_record('user', array('id' => $goal->usermodified));
//   				echo '<div class="last_dates">'.get_string('lastdateteacher','checkoutcome').$this->date_fr('d-M-Y H:i',$goal->timemodified).' ('.$teacher->firstname.' '.$teacher->lastname.')</div>';
//   				echo '<br>';
//   			}  			
  			echo get_string('goal','checkoutcome');
  			echo $OUTPUT->box_start();
  			if (!empty($goal) && !empty($goal->goal)) {
  				echo $goal->goal;
  			} else {
  				echo '<p>'.get_string('nogoal','checkoutcome').'</p>';
  			}
  			echo $OUTPUT->box_end();
  			
  			echo get_string('appraisal','checkoutcome');
  			echo $OUTPUT->box_start();
  			if (!empty($goal) && !empty($goal->appraisal)) {
  				echo $goal->appraisal;
  			} else {
  				echo '<p>'.get_string('noappraisal','checkoutcome').'</p>';
  			}
  			echo $OUTPUT->box_end();  			
  		}

  		// Footer
  		$this->view_footer();
    }
    
    /**
     * Displays student description screen
     */
    function view_studentdescription() {
    	global $DB,$OUTPUT, $CFG;

    	$goalid  = optional_param('goalid', 0, PARAM_INT);
    	if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $context = context_module::instance($this->cm->id);
        }

    	 
    	$retview = null;
    	$retview = new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id,'selected_periodid' => $this->selected_period->id));
    	    	
    	$goal = null;
    	$goal = $DB->get_record('checkoutcome_period_goals', array('userid' => $this->userid,'period' => $this->selected_period->id));
    		
    	$mform = new mod_checkoutcome_studentdescription_form(null,
    				array('checkoutcome'=>$this->checkoutcome->id,
    						'contextid'=>$context->id,
    						'goal'=>$goal,
    						'userid'=>$this->userid,
    						'periodid'=>$this->selected_period->id,
    						'msg' => get_string('student_description', 'checkoutcome')));
    	
    		if ($mform->is_cancelled()) {
    			redirect($retview);
    		} else if ($mform->get_data()) {
    			checkoutcome_edit_studentdescription($mform, $this->checkoutcome->id, $this->selected_period->id);
    			die();
    		}
    		
    		// Print page
    		$this->view_header();
    		
    		echo $OUTPUT->heading(format_string($this->checkoutcome->name),1);    		
    		 
    		// Display period infos
    		if (!$this->isDefaultPeriod()) {
    			// Display selected period name and dates
				if($this->selected_period->startdate!=null && $this->selected_period->startdate!=0)
				{
					echo $OUTPUT->heading($this->selected_period->name.' ('.$this->date_fr('d M Y',$this->selected_period->startdate).' - '.$this->date_fr('d M Y',$this->selected_period->enddate).')');
				}
				else
				{
					echo $OUTPUT->heading($this->selected_period->name);
				}    			
    		}
    		
    		$mform->display();
    		
    		$this->view_footer();
    		
    }
    
    /**
     * Displays header of a screen
     */
    function view_header() {
    	global $PAGE, $OUTPUT;
    
    	$PAGE->set_title($this->pagetitle);
    	$PAGE->set_heading($this->course->fullname);
    
    	echo $OUTPUT->header();    	
    	
    }
    
    /**
     * Displays footer of a screen
     */
    function view_footer() {
    	global $OUTPUT;
    	echo $OUTPUT->footer();
    }
    
    /**
     * Updates grades selected by teacher in mdl_grade_grades
     * Updates also mdl_grade_grades_history
     * @param Array of Integers $newoptions
     */
    function updateoptions($newoptions) {
    	global $DB;
		
    	if (!is_array($newoptions)) {
    		// Something has gone wrong, so update nothing
    		return;
    	}
    	//foreach ($this->category_items as $categ_it)
	    	$i = 0;
	    	foreach ($this->items as $item) {
	    		
	    		$newvalue = null;
	    		if ($newoptions[$i] != -1) {
	    			$newvalue = $newoptions[$i];
	    		}
	    		
	    		$ch_item = $DB->get_record('checkoutcome_item', array('gradeitem' => $item[0]->id));
	    		
	    		$grade = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem' => $item[1]->id,'userid' => $this->studentid, 'period' => $this->selected_period->id));
	    		 
				if (!$grade) {	    				
	    			if ($newvalue == -1) {
	    				$i++;
	    				continue;
	    			}
    				$new_grade = new stdClass();
    				$new_grade->checkoutcomeitem = $item[1]->id;
    				$new_grade->userid = $this->studentid;
    				$new_grade->period = $this->selected_period->id;
    				$new_grade->grade = $newvalue;
    				$new_grade->usermodified = $this->userid;
    				$new_grade->timecreated = $new_grade->timemodified = time();
    				if ($id = $DB->insert_record('checkoutcome_teachergrading', $new_grade)) {
    					$new_grade_history = new stdClass();
    					$new_grade_history->oldid = $id;
    					$new_grade_history->action = 1;
    					$new_grade_history->source = 'grade';
    					$new_grade_history->grade = $newvalue;
    					$new_grade_history->usermodified = $this->userid;
    					$new_grade_history->timecreated = $new_grade->timecreated;
    					$DB->insert_record('checkoutcome_teachgrad_histo', $new_grade_history);
    				}    					
    			} else {    					
    				if ($newvalue == -1) {
    					$grade->grade = 0;
    				} else {
    					$grade->grade = $newvalue;
    				}
    				$grade->usermodified = $this->userid;
    				$grade->timemodified = time();
    				if ($id = $DB->update_record('checkoutcome_teachergrading', $grade)) {
    					$new_grade_history = new stdClass();
    					$new_grade_history->oldid = $grade->id;
    					if ($newvalue == -1) {
    						$new_grade_history->action = 3;
    					} else {
    						$new_grade_history->action = 2;
    					}    						
    					$new_grade_history->source = 'grade';
    					$new_grade_history->grade = $newvalue;
    					$new_grade_history->usermodified = $this->userid;
    					$new_grade_history->timecreated = $grade->timemodified;
    					$DB->insert_record('checkoutcome_teachgrad_histo', $new_grade_history);
    				}
    			}
				
				
				//Export grades to the Grader report
				$scales = $DB->get_records('checkoutcome_teachergrading', array('checkoutcomeitem' => $ch_item->id,'userid' => $this->studentid));
				foreach($scales as $sc)
				{
					if(($sc->grade)>$newvalue)
					{
						$newvalue=$sc->grade;
					}
				}
					
				$grade2 = $DB->get_record('grade_grades', array('itemid' => $ch_item->gradeitem,'userid' => $this->studentid));
				
				if (!$grade2) {
    				$new_grade2 = new stdClass();
    				$new_grade2->itemid = $ch_item->gradeitem;
    				$new_grade2->userid = $this->studentid;
    				$new_grade2->rawgrademax = 100;
    				$new_grade2->usermodified = $this->userid;
					$new_grade2->finalgrade = $newvalue;
    				$new_grade2->timecreated = $new_grade2->timemodified = time();
					$DB->insert_record('grade_grades', $new_grade2);		
    			} else {
					$grade2->usermodified = $this->userid;
					$grade2->finalgrade = $newvalue;
					$grade2->timemodified = time();
					$DB->update_record('grade_grades', $grade2);

    			}
	    	$i++;
    	}
    	
    }
    
    /**
     * Updates count goals selected by teacher in mdl_checkoutcome_item
     * @param Array of Integers $newgoals
     */
    function update_count_goals($newgoals) {
    	global $DB;    
    	if (!is_array($newgoals)) {
    		// Something has gone wrong, so update nothing
    		return;
    	}    
		
    	foreach ($newgoals as $itemid =>$newvalue) {    		 
    		if ($item = $DB->get_record('checkoutcome_item', array('id' => $itemid))) {
    			$item->countgoal = $newvalue;
    			$item->timemodified = time();
    			if (!$id = $DB->update_record('checkoutcome_item', $item)) {
    				print_error('database_update_failed','checkoutcome');
    			}
    		} else {
    			print_error('no_item_found','checkoutcome');
    		}
    				
    	}    	 
    }
    
    /**
     * Updates counts updated by student in mdl_checkoutcome_selfgrading
     * @param Array of Integers $newcounts
     */
    function update_counts($newcounts) {
    	global $DB;
				
    	if (!is_array($newcounts)) {
    		// Something has gone wrong, so update nothing
    		//return;
    	}				
		
    	foreach ($newcounts as $itemid =>$newvalue) {
			if ($sgrade = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $itemid, 'userid' => $this->userid, 'period' => $this->selected_period->id))) {
    			$sgrade->count = $newvalue;
    			$sgrade->timemodified = $sgrade->counttime = time();
    			if ($id = $DB->update_record('checkoutcome_selfgrading', $sgrade)) {
    				$new_sgrade_history = new stdClass();
    				$new_sgrade_history->oldid = $id;
    				$new_sgrade_history->action = 2;
    				$new_sgrade_history->source = 'count';
    				$new_sgrade_history->count = $newvalue;
    				$new_sgrade_history->timecreated = $new_sgrade_history->counttime = $sgrade->timemodified;
    				$DB->insert_record('checkoutcome_selfgrad_histo', $new_sgrade_history);
    			} else {
    				print_error('database_update_failed','checkoutcome');
    			}
    		} else {
    			// create new item
    			$new_sgrade = new stdClass();
    			$new_sgrade->checkoutcomeitem = $itemid;
    			$new_sgrade->userid = $this->userid;
    			$new_sgrade->period = $this->selected_period->id;
    			$new_sgrade->count = $newvalue;
    			$new_sgrade->timecreated = $new_sgrade->timemodified = $new_sgrade->counttime = time();
    			if ($id = $DB->insert_record('checkoutcome_selfgrading', $new_sgrade)) {
    				$new_sgrade_history = new stdClass();
    				$new_sgrade_history->oldid = $id;
    				$new_sgrade_history->action = 1;
    				$new_sgrade_history->source = 'count';
    				$new_sgrade_history->count = $newvalue;
    				$new_sgrade_history->timecreated = $new_sgrade_history->counttime = $new_sgrade->timecreated;
    				$DB->insert_record('checkoutcome_selfgrad_histo', $new_sgrade_history);
    			}    
    		}
    	}
    }
    
    /**
     * Update selected entries in mdl_checkoutcome_items with new category and/or display values 
     * @param Array of Integers $itemids
     * @param Integer $category
     * @param Integer $display
     */
    function updateoutcomes($itemids, $category, $display) {
    	global $DB;
    
    	if (!is_array($itemids)) {
    		// Something has gone wrong, so update nothing
    		return;
    	}
    
    	foreach ($itemids as $itemid) {    		 
    		if ($ch_item = $DB->get_record('checkoutcome_item', array('id' => $itemid))) {
				if ($category != -1) {
					$ch_item->category = $category;					
				}
				if ($display != -1) {
					$ch_item->display = $display;
				}				
				$ch_item->timemodified=time();
				// Update Checkoutcome item
				$DB->update_record("checkoutcome_item", $ch_item);    			
    		}
    	}	 
    }
	
	/**
     * Update selected entries in mdl_checkoutcome_periods with new locked periods and/or reference period 
     * @param Array of Integers $itemids
     * @param Integer $lock
     * @param Integer $markperiod
     */
    function updateperiods($periods, $lock) {
    	global $DB;
		
    	if (!is_array($periods)) {
    		// Something has gone wrong, so update nothing
    		return;
    	}
    
    	foreach ($periods as $period) { 

    		if ($ch_period = $DB->get_record('checkoutcome_periods', array('id' => $period))) {
				if ($lock != -1) {
					$ch_period->lockperiod = $lock;	
					$ch_period->timemodified=time();					
				}			
				
				// Update Checkoutcome period
				$DB->update_record("checkoutcome_periods", $ch_period);    			
    		}
    	}	 
    }
    
    /**
     * Updates grades selected by student (self grading) in mdl_checkoutcome_selfgrading
     * @param Array of Integers $changedselects
     */
    function ajaxupdateselects($changedselects) {
    	global $DB;
		
    	foreach ($this->items as $item) {
    		if (array_key_exists($item[1]->id, $changedselects)) {
    			$itemid = $item[1]->id;
    			$newvalue = $changedselects[$itemid];
    			if ($newvalue == -1) {
    				$newvalue = null;
    			}
    			
    			$ch_item = $DB->get_record('checkoutcome_item', array('id' => $itemid));
    			
    			$ch_selfgrading = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $ch_item->id,'userid' => $this->userid,'period' => $this->selected_period->id));
    			
    			if (!$ch_selfgrading) {
    				$new_selfg = new stdClass();
    				$new_selfg->checkoutcomeitem = $ch_item->id;
    				$new_selfg->userid = $this->userid;
    				$new_selfg->period = $this->selected_period->id;
    				$new_selfg->grade = $newvalue;
    				$new_selfg->timecreated = $new_selfg->timemodified = time();
    				if ($id = $DB->insert_record('checkoutcome_selfgrading', $new_selfg)) {
    					$new_grade_history = new stdClass();
    					$new_grade_history->oldid = $id;
    					$new_grade_history->action = 1;
    					$new_grade_history->source = 'grade';
    					$new_grade_history->grade = $newvalue;
    					$new_grade_history->timecreated = $new_selfg->timecreated;
    					$DB->insert_record('checkoutcome_selfgrad_histo', $new_grade_history);
    				}    					
    			} else {
    				$ch_selfgrading->grade = $newvalue;
    				$ch_selfgrading->timemodified = time();
    				if ($id = $DB->update_record('checkoutcome_selfgrading', $ch_selfgrading)) {
    					$new_grade_history = new stdClass();
    					$new_grade_history->oldid = $ch_selfgrading->id;
    					$new_grade_history->action = 2;
    					$new_grade_history->source = 'grade';
    					$new_grade_history->grade = $newvalue;
    					$new_grade_history->timecreated = $ch_selfgrading->timemodified;
    					$DB->insert_record('checkoutcome_selfgrad_histo', $new_grade_history);
    				}    				
    			}    			
    		} 
    	}
    }
    
    /**
     * Updates grades selected by teacher in mdl_grade_grades
     * Updates also mdl_grade_grades_history
     * @param Array of Integers $changedselects
     */
    function ajaxupdateteacherselects($changedselects) {
    	global $DB;
    	foreach ($this->items as $item) {
    		if (array_key_exists($item[1]->id, $changedselects)) {  

				$itemid = $item[1]->id;
    			$newvalue = $changedselects[$itemid];
    			if ($newvalue == -1) {
    				$newvalue = null;
    			}
    			
    			$ch_item = $DB->get_record('checkoutcome_item', array('id' => $itemid));
    			
    			$grade = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem' => $ch_item->id,'userid' => $this->studentid,'period' => $this->selected_period->id));
    				 
    			if (!$grade) {
    				$new_grade = new stdClass();
    				$new_grade->checkoutcomeitem = $ch_item->id;
    				$new_grade->userid = $this->studentid;
    				$new_grade->period = $this->selected_period->id;
    				$new_grade->grade = $newvalue;
    				$new_grade->usermodified = $this->userid;
    				$new_grade->timecreated = $new_grade->timemodified = time();
    				if ($id = $DB->insert_record('checkoutcome_teachergrading', $new_grade)) {
    					$new_grade_history = new stdClass();
    					$new_grade_history->oldid = $id;
    					$new_grade_history->action = 1;
    					$new_grade_history->source = 'grade';
    					$new_grade_history->usermodified = $this->userid;
    					$new_grade_history->grade = $newvalue;
    					$new_grade_history->timecreated = $new_grade->timecreated;
    					$DB->insert_record('checkoutcome_teachgrad_histo', $new_grade_history);
    				}    				
    			} else {
    				$grade->grade = $newvalue;
    				$grade->usermodified = $this->userid;
    				$grade->timemodified = time();
    				if ($id = $DB->update_record('checkoutcome_teachergrading', $grade)) {
    					$new_grade_history = new stdClass();
    					$new_grade_history->oldid = $grade->id;
    					$new_grade_history->action = 2;
    					$new_grade_history->source = 'grade';
    					$new_grade_history->grade = $newvalue;
    					$new_grade_history->usermodified = $this->userid;
    					$new_grade_history->timecreated = $grade->timemodified;
    					$DB->insert_record('checkoutcome_teachgrad_histo', $new_grade_history);
    				}
    			}  	

				//Export grades to the Grader report
				$scales = $DB->get_records('checkoutcome_teachergrading', array('checkoutcomeitem' => $ch_item->id,'userid' => $this->studentid));
				foreach($scales as $sc)
				{
					if(($sc->grade)>$newvalue)
					{
						$newvalue=$sc->grade;
					}
				}
					
				$grade2 = $DB->get_record('grade_grades', array('itemid' => $ch_item->gradeitem,'userid' => $this->studentid));
				
				if (!$grade2) {
    				$new_grade2 = new stdClass();
    				$new_grade2->itemid = $ch_item->gradeitem;
    				$new_grade2->userid = $this->studentid;
    				$new_grade2->rawgrademax = 100;
    				$new_grade2->usermodified = $this->userid;
					$new_grade2->finalgrade = $newvalue;
    				$new_grade2->timecreated = $new_grade2->timemodified = time();
					$DB->insert_record('grade_grades', $new_grade2);		
    			} else {
					$grade2->usermodified = $this->userid;
					$grade2->finalgrade = $newvalue;
					$grade2->timemodified = time();
					$DB->update_record('grade_grades', $grade2);

    			}
    		}
    	}    	
    }
    
    /**
     * Updates feedback of the teacher in mdl_grade_grades
     * Updates also mdl_grade_grades_history
     * @param Integer $itemid (checkoutcome item)
     * @param String $comment
     */
    function updateteachercomment($itemid, $comment) {
    	global $DB,$OUTPUT;
    	
    		if ($grade = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem' => $itemid, 'userid' => $this->studentid, 'period' => $this->selected_period->id))) {
    			$grade->comment = $comment;
    			$grade->usermodified = $this->userid;
    			$grade->timemodified = $grade->commenttime = time();
    			if ($id = $DB->update_record('checkoutcome_teachergrading', $grade)) {
    				$new_grade_history = new stdClass();
    				$new_grade_history->oldid = $grade->id;
    				$new_grade_history->action = 2;
    				$new_grade_history->source = 'comment';
    				$new_grade_history->usermodified = $this->userid;
    				$new_grade_history->comment = $comment;
    				$new_grade_history->timecreated = $grade->timemodified;
    				$DB->insert_record('checkoutcome_teachgrad_histo', $new_grade_history);
    			}    				
    		} else {
    			$new_grade = new stdClass();
    			$new_grade->checkoutcomeitem = $itemid;
    			$new_grade->userid = $this->studentid;
    			$new_grade->period = $this->selected_period->id;
    			$new_grade->comment = $comment;
    			$new_grade->usermodified = $this->userid;
    			$new_grade->timecreated = $new_grade->timemodified = $new_grade->commenttime = time();
    			if ($id = $DB->insert_record('checkoutcome_teachergrading', $new_grade)) {
    				$new_grade_history = new stdClass();
    				$new_grade_history->oldid = $id;
    				$new_grade_history->action = 1;
    				$new_grade_history->source = 'comment';
    				$new_grade_history->usermodified = $this->userid;
    				$new_grade_history->comment = $comment;
    				$new_grade_history->timecreated = $new_grade->timecreated;
    				$DB->insert_record('checkoutcome_teachgrad_histo', $new_grade_history);
    			}
    		}    			
    	   	
    }
    
    /**
     * Updates comment of the student in mdl_checkoutcome_selfgrading 
     * @param Integer $itemid
     * @param String $comment
     * @throws Exception
     */
    function updatecomment($itemid, $comment) {
    	global $DB;
    	 
    	if ($this->items[$itemid] && $this->items[$itemid][1]->id == $itemid) {
    		 
    		$ch_item = $DB->get_record('checkoutcome_item', array('id' => $itemid));
    		 
    		if ($sg_item = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $itemid, 'userid' => $this->userid, 'period' => $this->selected_period->id))) {
    			$sg_item->comment = $comment;
    			$sg_item->timemodified = $sg_item->commenttime = time();
    			if ($id = $DB->update_record('checkoutcome_selfgrading', $sg_item)) {
    				$new_grade_history = new stdClass();
    				$new_grade_history->oldid = $sg_item->id;
    				$new_grade_history->action = 2;
    				$new_grade_history->source = 'checkoutcomecomment';
    				$new_grade_history->comment = $comment;
    				$new_grade_history->timecreated = $sg_item->timemodified;
    				$DB->insert_record('checkoutcome_selfgrad_histo', $new_grade_history);
    			}
    		} else {
    			$new_selfg = new stdClass();
    			$new_selfg->checkoutcomeitem = $ch_item->id;
    			$new_selfg->userid = $this->userid;
    			$new_selfg->period = $this->selected_period->id;
    			$new_selfg->comment = $comment;
    			$new_selfg->timecreated = $new_selfg->timemodified = $new_selfg->commenttime = time();
    			if ($id = $DB->insert_record('checkoutcome_selfgrading', $new_selfg)) {
    				$new_grade_history = new stdClass();
    				$new_grade_history->oldid = $id;
    				$new_grade_history->action = 1;
    				$new_grade_history->source = 'comment';
    				$new_grade_history->comment = $comment;
    				$new_grade_history->timecreated = $new_selfg->timecreated;
    				$DB->insert_record('checkoutcome_selfgrad_histo', $new_grade_history);
    			}
    		}
    	}
    }
    
    /**
     * Deletes comment of the student for a given item in mdl_checkoutcome_selfgrading
     * @param Integer $id
     */
    function deletecomment($id) {
    	global $DB;
    	
    	$sg_item = $DB->get_record('checkoutcome_selfgrading', array('id' => $id));
    	
    	if ($sg_item) {
    		$sg_item->comment = '';
    		$sg_item->timemodified = $sg_item->commenttime = time();
    		if ($id = $DB->update_record('checkoutcome_selfgrading', $sg_item)) {
    			$new_grade_history = new stdClass();
    			$new_grade_history->oldid = $sg_item->id;
    			$new_grade_history->action = 3;
    			$new_grade_history->source = 'comment';
    			$new_grade_history->timecreated = $sg_item->timemodified;
    			$DB->insert_record('checkoutcome_selfgrad_histo', $new_grade_history);
    		}
    	}
    	
    	// delete also linked documents    	
    	$DB->delete_records('checkoutcome_document', array('gradeid' => $id));
    }
    
    /**
     * Deletes feedback of the teacher for a given item and a given student in mdl_checkoutcome_teachergrading
     * @param Integer $id
     */
    function deleteteachercomment($id) {
    	global $DB;
    	 
    	$grade = $DB->get_record('checkoutcome_teachergrading', array('id' => $id));
    	 
    	if ($grade) {
    		$grade->comment = '';
    		$grade->usermodified = $this->userid;
    		$grade->timemodified = $grade->commenttime = time();
    		if ($id = $DB->update_record('checkoutcome_teachergrading', $grade)) {
    			$new_grade_history = new stdClass();
    			$new_grade_history->oldid = $grade->id;
    			$new_grade_history->action = 3;
    			$new_grade_history->source = 'comment';
    			$new_grade_history->timecreated = $grade->timemodified;
    			$DB->insert_record('checkoutcome_selfgrad_histo', $new_grade_history);
    		}
    	}    	
    }
    
    /**
     * 
     * @param unknown_type $itemid
     * @param unknown_type $linkurl
     */
    function update_item_link($itemid, $linkurl) {
    	global $DB;
    	if ($item = $DB->get_record('checkoutcome_item', array('id' => $itemid))) {
    		$item->resource = $linkurl;
    		$DB->update_record('checkoutcome_item', $item);
    	} else {
    		print_error('no_item_found','ckeckoutcome');
    	}    	
    }
    
    /**
     * 
     * @param unknown_type $itemid
     */
    function delete_item_link($itemid) {
    	global $DB;
    	if ($item = $DB->get_record('checkoutcome_item', array('id' => $itemid))) {
    		$item->resource = null;
    		$DB->update_record('checkoutcome_item', $item);
    	}
    }
    
    
    /**
     * Displays tabs
     * @param String $currenttab
     */
    function view_tabs($currenttab) {
    	$tabs = array();
    	$row = array();
    	$inactive = array();
    	$activated = array();
    
    	
    	if ($this->canupdateown()) {
    		$row[] = new tabobject('view', new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id)), get_string('view', 'checkoutcome'));
    	} 
    	else if ($this->canpreview()) {
    		$row[] = new tabobject('preview', new moodle_url('/mod/checkoutcome/view.php', array('id' => $this->cm->id, 'for_preview' => '1')), get_string('preview', 'checkoutcome'));
    	}
    	if (has_capability('moodle/course:manageactivities', $this->context)) {
    		$row[] = new tabobject('setting', new moodle_url('/mod/checkoutcome/setting.php', array('id' => $this->cm->id)), get_string('setting', 'checkoutcome'));
    	}
    	if ($this->canupdateother()) {
    		$row[] = new tabobject('list_gradings', new moodle_url('/mod/checkoutcome/list_gradings.php', array('id' => $this->cm->id, 'group' => 0)), get_string('list_gradings', 'checkoutcome'));
    	}    	
     	if ($this->canviewreports()) {
     		$row[] = new tabobject('report', new moodle_url('/mod/checkoutcome/report.php', array('id' => $this->cm->id)), get_string('report', 'checkoutcome'));
     	}     	
    
    	if (count($row) == 1) {
    		// No tabs for students
    	} else {
    		$tabs[] = $row;
    	}    

    
    	print_tabs($tabs, $currenttab, $inactive, $activated);
    }
    
    
    /**
     * Displays tabs for the setting screen
     * @param String $currenttab
     */
    function view_tabs_setting($currenttab) {
    	$tabs = array();
    	$row = array();
    	$inactive = array();
    	$activated = array();    	

    	
    	if (has_capability('moodle/course:manageactivities', $this->context)) {
    		$row[] = new tabobject('edit', new moodle_url('/mod/checkoutcome/edit.php', array('id' => $this->cm->id)), get_string('edit', 'checkoutcome'));
    	}
    	if (has_capability('moodle/course:manageactivities', $this->context)) {
    		$row[] = new tabobject('list_cat', new moodle_url('/mod/checkoutcome/list_cat.php', array('id' => $this->cm->id)), get_string('list_cat', 'checkoutcome'));
    	}
    	if (has_capability('moodle/course:manageactivities', $this->context)) {
    		$row[] = new tabobject('list_disp', new moodle_url('/mod/checkoutcome/list_disp.php', array('id' => $this->cm->id)), get_string('list_disp', 'checkoutcome'));
    	}
    	if (has_capability('moodle/course:manageactivities', $this->context)) {
    		$row[] = new tabobject('list_period', new moodle_url('/mod/checkoutcome/list_period.php', array('id' => $this->cm->id)), get_string('list_period', 'checkoutcome'));
    	}    	
    	
    	if (count($row) == 1) {
    		// No tabs for students
    	} else {
    		$tabs[] = $row;
    	}
    	
    	print_tabs($tabs, $currenttab, $inactive, $activated);
    }
    
    
    /**
     * Displays tabs for the report screen
     * @param String $currenttab
     */
    function view_tabs_report($currenttab) {
    	$tabs = array();
    	$row = array();
    	$inactive = array();
    	$activated = array();
    
    	
    	//if (has_capability('moodle/course:manageactivities', $this->context)) {
		if ($this->canviewreports()) {
    		$row[] = new tabobject('summary', new moodle_url('/mod/checkoutcome/summary.php', array('id' => $this->cm->id)), get_string('summary', 'checkoutcome'));
    	}
    	//if (has_capability('moodle/course:manageactivities', $this->context)) {
		if ($this->canviewreports()) {
    		$row[] = new tabobject('export', new moodle_url('/mod/checkoutcome/export.php', array('id' => $this->cm->id)), get_string('export', 'checkoutcome'));
    	}
    	
    	$tabs[] = $row;    	
    	 
    	print_tabs($tabs, $currenttab, $inactive, $activated);
    }
    
    
    /**
     * Get instance of mdl_grade_grades_history with embedded user name
     * corresponding to the last modified feedback
     * @param instance of mdl_grade_grades $grade
     * @return object|NULL
     */
    function getFeedbackHisto($grade) {
    	global $DB;
    	$sql = "SELECT timecreated,usermodified FROM {checkoutcome_teachgrad_histo} WHERE oldid=".$grade->id." and source='comment' order by timecreated desc;"; 
    	$histos = $DB->get_recordset_sql($sql);
    	
    	foreach ($histos as $h) {
    		
    		if ($h->usermodified != null) {
    			$user = $DB->get_record('user', array('id' => $h->usermodified));
    			$h->username = $user->firstname.' '.$user->lastname;
    		}
    		return $h;
    	}
    	return null;
    }
    
   /**
     * Get instance of mdl_checkoutcome_teachgrad_histo with embedded user name
     * corresponding to the last modified grade
     * @param instance of mdl_checkoutcome_teachergrading $grade
     * @return object|NULL
     */
    function getGradeHisto($grade) {
    	global $DB;
    	$sql = "SELECT timecreated,usermodified FROM {checkoutcome_teachgrad_histo} WHERE oldid=".$grade->id." and source='grade' order by timecreated desc;";
    	$histos = $DB->get_recordset_sql($sql);    	 
    	foreach ($histos as $h) {    
    		if ($h->usermodified != null) {
    			$user = $DB->get_record('user', array('id' => $h->usermodified));
    			$h->username = $user->firstname.' '.$user->lastname;
    		}
    		return $h;
    	}
    	return null;
    }
    
    /**
     * Get instance of mdl_checkoutcome_selfgrad_histo
     * corresponding to the last modified grade
     * @param instance of mdl_checkoutcome_selfgrading $grade
     * @return object|NULL
     */
    function getSelfGradeHisto($grade) {
    	global $DB;
    	$sql = "SELECT timecreated FROM {checkoutcome_selfgrad_histo} WHERE oldid=".$grade->id." and source='grade' order by timecreated desc;";
    	$histos = $DB->get_recordset_sql($sql);    
    	foreach ($histos as $h) {    
    		return $h;
    	}
    	return null;
    }
       
    
    /**
     * Returns possible itemnumber for database checkoutcome_item
     * @return Integer
     */
    function getCheckoutcomeNewItemNumber() {
    	global $DB;
    	
    	$sql = 'SELECT max(itemnumber) AS max FROM {checkoutcome_item} WHERE checkoutcome = '.$this->checkoutcome->id.';';
    	
    	$itemnumber = NULL;
    	if ($max = $DB->get_record_sql($sql)) {
    		$itemnumber = $max->max + 1;
    	} else {
    		$itemnumber = 1;
    	}
    	return $itemnumber;
    }
    
    
    /**
     * Returns count of grades (student + teacher) already registered for this item
     * @param Array $item
     * @return Integer
     */
    function itemInUse($item) {
    	global $DB;
    	//Counting teacher grades on this item
    	$teachers_grades_count = $DB->count_records('grade_grades',array('itemid' => $item[0]->id));
    	
    	//Counting student grades on this item
    	$students_grades_count = $DB->count_records('checkoutcome_selfgrading',array('checkoutcomeitem' => $item[1]->id));
    		
    	return $inuse = $teachers_grades_count + $students_grades_count;
    }
    
    
    /**
     * Returns count of items using this category 
     * @param Array $item
     * @return Integer
     */
    function categoryInUse($categid) {
    	global $DB;
    	return $DB->count_records('checkoutcome_item', array('category'=> $categid));
    }
    
    
    /**
     * Returns count of items using this display
     * @param Integer $dispid
     * @return Integer
     */
    function displayInUse($dispid) {
    	global $DB;
    	return $DB->count_records('checkoutcome_item', array('display'=> $dispid));
    }
    
    /**
     * Returns count of selfgradings, teachergradings, or period goals items using this period
     * @param Integer $periodid
     * @return Integer
     */
    function periodInUse($periodid) {
    	global $DB;
    	$selfgradings = $DB->count_records('checkoutcome_selfgrading', array('period'=> $periodid));
    	$teachergradings = $DB->count_records('checkoutcome_teachergrading', array('period'=> $periodid));
    	$period_goals = $DB->count_records('checkoutcome_period_goals', array('period'=> $periodid));
    	
    	return ($selfgradings + $teachergradings + $period_goals);
    }
    
    /**
     * Returns date of last update done by student
     * @return Integer|unknown
     */
    function getLastStudentChangeDate() {
    	$lastdate = 0;
    	if ($this->items != null) {
	    	foreach ($this->items as $item) {
	    		if ($item[2] != null && $item[2]->timemodified > $lastdate) {
	    			$lastdate = $item[2]->timemodified;
	    		}    		
	    	}
    	}    	
    	if ($lastdate == 0) {
    		return null;
    	} else {
    		return $lastdate;
    	}
    	
    }
 
    
    /**
     * Returns 
     * if $i = 1 : date of last update done by teacher
     * if $ i =2 : id of the teacher who has done the last update
     * @param Integer $i
     * @return Integer
     */
    function getLastTeacherChange($i) {
    	$lastdate = 0;
    	$teacherid =null;
    	if ($this->items != null) {
	    	foreach ($this->items as $item) {
	    		if ($item[3] != null && $item[3]->timemodified > $lastdate) {
	    			$lastdate = $item[3]->timemodified;
	    			$teacherid = $item[3]->usermodified;
	    		}    		
	    	}
    	}    	
    	switch($i) {
    		case 1 :
    			return $lastdate;
    			break;
    		case 2 :
    			return $teacherid;
    			break;
    	}   	
    }
    
    /**
     * Returns true if the scale item has been selected by the user, else false
     * @param Array $item
     * @param String $scaleitem
     * @return boolean
     */
    function isSelectedScaletitem($item,$scaleitem) {
    	global $DB,$USER;
    
    	if ($this->studentid) {
    		$userid = $this->studentid;
    	} else {
    		$userid = $USER->id;
    	}
    
    	$grade = $DB->get_field('checkoutcome_selfgrading', 'grade', array('checkoutcomeitem' => $item[1]->id, 'userid' => $userid, 'period' => $this->selected_period->id));
    
    	if ($grade != null && ($grade == $scaleitem)) {
    		return true;
    	} else {
    		return false;
    	}
    }
     
    /**
     * Returns true if scale items has been selected by teacher, else false
     * @param Array $item
     * @param String $scaleitem
     * @return boolean
     */
    function isSelectedTeacherScaleitem($item,$scaleitem) {
    	global $DB,$USER;
    
    	if ($this->studentid) {
    		$userid = $this->studentid;
    	} else {
    		$userid = $USER->id;
    	}
    
    	$grade = $DB->get_field('checkoutcome_teachergrading', 'grade', array('checkoutcomeitem' => $item[1]->id, 'userid' => $userid, 'period' => $this->selected_period->id));
    	 
    	if ($grade != null && ($grade == $scaleitem)) {
    		return true;
    	} else {
    		return false;
    	}
    }     
    
    /**
     * Insert an entry in mdl_checkoutcome_item table in database
     * @param instance of mdl_grade_items $gitem
     * @param instance of mdl_grade_outcomes $outcome
     * @throws Exception
     */
    function insert_checkoutcome_item($gitem,$itemnumber) {
    	global $DB;
    	 
    	$outcome = $DB->get_record('grade_outcomes', array('id' => $gitem->outcomeid));
    	$ch_item = new stdClass();
    	$ch_item->checkoutcome = $this->checkoutcome->id;
    	$ch_item->itemnumber = $itemnumber;
    	$ch_item->gradeitem = $gitem->id;
    	$ch_item->scaleid = $outcome->scaleid;
    	$ch_item->category = 0; // link to NA category first
    	$ch_item->timecreated = $ch_item->timemodified = time();
    	if ($id = $DB->insert_record('checkoutcome_item', $ch_item)) {
    		return $id;
    	} else {
    		throw new Exception();
    	}
    }
    
    /**
     * Check if selected period is default period
     * @return boolean
     */
    function isDefaultPeriod() {
    	if ($this->selected_period->startdate == 0 && $this->selected_period->enddate == 0) {
    		return false;
    	} else {
    		return false;
    	}
    }
    
    
    /**
     * Builds a pdf file summarizing the datas of the module
     * available for student himself with his own datas 
     * or for teacher with the datas from a given student 
     */
    function exportPDF() {
    	global $USER,$DB,$CFG;    	
    	    	
    	$exporter = null;
    	$author = null;
    	if ($this->studentid == null) {
    		// student is exporting : exporter and author are the same person
    		$author = $exporter = $DB->get_record('user', array('id' => $this->userid));
    	} else {
    		// exporter is current user (teacher)
    		$exporter = $DB->get_record('user', array('id' => $this->userid));
    		// author is student
    		$author = $DB->get_record('user', array('id' => $this->studentid));
    	}
    	
    	$authorname = $author->firstname . ' ' . $author->lastname;
    	$exportername = $exporter->firstname . ' ' . $exporter->lastname;
    	
    	// Get goal of the period if existing
    	$goalid  = optional_param('goalid', 0, PARAM_INT);
    	if ($goalid) {
    		$goal = $DB->get_record('checkoutcome_period_goals', array('id' => $goalid));
    	}
    	
    	// create new PDF document
		$pdf = new checkoutcome_pdf('P', 'mm', 'A4', true, 'UTF-8',$this->checkoutcome->name, $authorname);
		
		// set document information
		$pdf->SetCreator($exportername);
		$pdf->SetAuthor($authorname);
		$pdf->SetTitle($this->checkoutcome->name);
		$pdf->SetSubject($this->checkoutcome->intro);
		$pdf->SetDisplayMode(50);
		
		
		//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

		// set default header data
		$pdf->SetHeaderData('/mod/checkoutcome/pix/logo.png', 30, $this->checkoutcome->name, $authorname);

		// set header and footer fonts
		$pdf->setHeaderFont(Array('helvetica', '', 10));
		$pdf->setFooterFont(Array('helvetica', '', 8));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont('courier');

		//set margins
		$pdf->SetMargins(15, 27, 15);
		$pdf->SetHeaderMargin(5);
		$pdf->SetFooterMargin(10);

		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, 25);
		

		//set image scale factor
		$pdf->setImageScale(1.25);

		//set exporter language
		$pdf->setLanguageArray($exporter->lang);
		

// ---------------------------------------------------------

		// add first page 
		$pdf->AddPage();		
		$pdf->Ln(60);
		
		// Module Title
		$pdf->SetFont('helvetica', 'B', 40);
		$pdf->Write(0, $this->checkoutcome->name, '', 0, 'C', true, 0, false, true, 0, 0, array(20,100));
		// Module description
		if (!empty($this->checkoutcome->intro)) {
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->writeHTML($this->checkoutcome->intro, true, false, false, false, 'C');
			$pdf->Ln(30);
		} else {
			$pdf->Ln(5);
		}	
		
		// Period name 
		$pdf->SetFont('helvetica', 'B', 30);
		$pdf->Write(0, $this->selected_period->name, '', 0, 'C', true, 0, false, true, 0, 0, array(20,100));
		$pdf->Ln(5);
		// Period decription
		if (!empty($this->selected_period->description)) {			
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->Write(0, $this->selected_period->description, '', 0, 'C', true, 0, false, true, 0, 0, array(20,100));
			$pdf->Ln(5);
		}			
		// Period dates
		$pdf->SetFont('helvetica', 'B', 10);
		$period_dates = ' ('.$this->date_fr('d M Y',$this->selected_period->startdate).' - '.$this->date_fr('d M Y',$this->selected_period->enddate).')';
		$pdf->Write(0, $period_dates, '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
		$pdf->Ln(10);		
		
		// Student name
		$pdf->SetFont('helvetica', 'B', 20);
		$pdf->Write(0, get_string('author','checkoutcome').$authorname, '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
		$pdf->Ln(60);		

		// Exported by
		$pdf->SetFont('helvetica', 'B', 10);
		$pdf->Write(0, get_string('exporter','checkoutcome').$exportername, '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
		
		// Date
		$date = $this->date_fr('d-M-Y',time());
		$pdf->SetFont('helvetica', 'B', 10);
		$pdf->Write(0, get_string('date_pdf','checkoutcome').$date, '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));

		// ---------------------------------------------------------
		
//Add page : student description of the period if existing
		if (!empty($goal) && !empty($goal->studentsdescription)) {
				
			$pdf->AddPage();
				
			$pdf->SetFont('helvetica', 'B', 20);
			$pdf->Write(0, get_string('student_description','checkoutcome'), '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
			$pdf->Ln(10);
				
			$pdf->SetFont('helvetica', '', 8);
			$description = '';
			$description.= '<p style="margin:10px;">'.$goal->studentsdescription.'</p>';
			$pdf->writeHTMLCell(0, 0, $pdf->GetX(), $pdf->GetY(), $description,1);			
			
		}
				

// ---------------------------------------------------------

//Add page : goals of the period if existing		
		if (!empty($goal)) {
			
			$pdf->AddPage();
			
			$pdf->SetFont('helvetica', 'B', 20);
			$pdf->Write(0, get_string('period_goals','checkoutcome'), '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
			$pdf->Ln(10);

			$pdf->SetFont('helvetica', 'B', 14);
			$goalscontent = '';
			$goalscontent.= get_string('goal','checkoutcome').'<br>';
			$pdf->writeHTML($goalscontent, true, false, false, false, '');		
			
			$pdf->SetFont('helvetica', '', 8);
			$goalscontent = '';
			if (!empty($goal) && !empty($goal->goal)) {
				$goalscontent.= '<p style="margin:10px;">'.$goal->goal.'</p>';
			} else {
				$goalscontent.= '<p style="margin:10px;">'.get_string('nogoal','checkoutcome').'</p>';
			}
			$pdf->writeHTMLCell(0, 0, $pdf->GetX(), $pdf->GetY(), $goalscontent,1);
			
//Add page : appraisal of the period if existing
			
			$pdf->AddPage();
			
			$pdf->SetFont('helvetica', 'B', 20);
			$pdf->Write(0, get_string('period_appraisals','checkoutcome'), '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
			$pdf->Ln(10);
			
			$pdf->SetFont('helvetica', 'B', 14);
			$goalscontent = '';
			$goalscontent.= get_string('appraisal','checkoutcome').'<br>';
			$pdf->writeHTML($goalscontent, true, false, false, false, '');
			
			$pdf->SetFont('helvetica', '', 8);
			$goalscontent = '';
			if (!empty($goal) && !empty($goal->appraisal)) {
				$goalscontent.= '<p style="margin:10px;">'.$goal->appraisal.'</p>';
			} else {
				$goalscontent.= '<p style="margin:10px;">'.get_string('noappraisal','checkoutcome').'</p>';
			}
			$pdf->writeHTMLCell(0, 0, $pdf->GetX(), $pdf->GetY(), $goalscontent,1);
				
		}
		
		
		
// ---------------------------------------------------------
		// change page
		$pdf->AddPage();
		$pdf->SetFont('helvetica', '', 8);		
		
		// Print Module datas
		$categoryContent ='';
		// Display items sorted by category
		if ($this->items != null && !empty($this->items)){
		
			// for each category list items
			$index = 1;
			$current_category = null;
			foreach ($this->items as $item) {
								
				// Change or not current category and display category title, begin table
				if ($current_category == null || $item[1]->category != $current_category->id) {
					
					//Print end of previous table if not first table
					if ($index > 1) {
						$categoryContent .= '</table>';						
						$pdf->writeHTML($categoryContent, true, false, false, false, '');					
						$pdf->AddPage();						
						$categoryContent = '';						
					} 
					$current_category = $this->categories[$item[1]->category];
					// Category title
					if ($current_category->id != 0) {
						$categoryContent.= '<h1>'.$index.' - '.$current_category->name.'</h1>';
						$categoryContent.= '<h4><i>'.$current_category->description.'</i></h4>';
					}					
					$index++;
					$categoryContent.= '<table cellspacing="0" cellpadding="1" border="1" width="100%">
					 			<tr style="text-align:center;page-break-inside : avoid;">
					 				<th width="10%">'.get_string('shortname','checkoutcome').'</th>
					 				<th width="60%">'.get_string('fullname','checkoutcome').'</th>
					 				<th width="10%">'.get_string('counter','checkoutcome').'</th>
					 				<th width="10%">'.get_string('student','checkoutcome').'</th>
					 				<th width="10%">'.get_string('teacher','checkoutcome').'</th>
					 			</tr>';
					 		
				}
				
				// Print Item
					//Get background color
					$color = '#fff';
					if ($item[1]->display != null) {
						$color = '#'.$this->displays[$item[1]->display]->color;
					}					
					//Get font color
					$fcolor = "#000";
					if ($item[1]->display != null && $this->displays[$item[1]->display]->iswhitefont) {
						$fcolor = '#fff';
					}
				$categoryContent .= '<tr valign="middle" style="vertical-align:center;page-break-inside:avoid;background-color:'.$color.';color:'.$fcolor.';">';
					// Print shortname
					$categoryContent .= '<td style="text-align:center;">'.$item[4]->shortname.'</td>';
					// Print fullname						
					$categoryContent .= '<td>'.$item[4]->fullname;
						//Print student comment and attached documents
						if ($item[2] != null && !empty($item[2]->comment)) {
							$categoryContent .= '<br>';
							$categoryContent .= '<span>';
								$categoryContent .= get_string('student_comment','checkoutcome');
								$categoryContent .= '<br><span>';
									//$categoryContent .= '<span style="background-color: #88EA83;color:#000;border: 1px solid #000000;font-style: italic;">'.$item[2]->comment.'</span>';
									$categoryContent .= '<span style="border: 1px solid #000000;font-style: italic;">'.$item[2]->comment.'</span>';
									$categoryContent .= ' <span style="font-size:70%;">['.$this->date_fr('d-M-Y H:i',$item[2]->commenttime).']</span>';
								$categoryContent .= '</span>';
							$categoryContent .= '</span>';
						
						//Print attached documents
						$documents = $DB->get_records('checkoutcome_document', array('gradeid' => $item[2]->id));
						if ($documents) {
							$categoryContent .= '<br><span>'.get_string('attached_documents','checkoutcome').'</span>';
							foreach ($documents as $doc) {
								$info = '';
								if (!empty($doc->description)) {
									$info = $doc->description;
								}
								if ($doc->url) {
									if (!mb_ereg("http",$doc->url)){ // fichier telecharge
										// l'URL a été correctement formée lors de la création du fichier
										$efile =  $CFG->wwwroot.'/pluginfile.php'.$doc->url;
									}
								} else{
									$efile = $doc->url;
								}
								$categoryContent .= '<br><span>';
									$categoryContent .= '<a href="'.$efile.'" style="color:inherit;">'.$doc->title .'</a> <span style="font-size:70%;">[' . $this->date_fr('d-M-Y H:i',$doc->timemodified) . ']</span> ';
								$categoryContent .= '</span>';
							}
							}
						}
						//Print teacher feedback
						//$categoryContent .= '<div>Teacher feedback</div>';
						if ($item[3] != null && !empty($item[3]->comment)) {
							$categoryContent .= '<br>';
							$categoryContent .= '<span>';
								$categoryContent .= get_string('teacher_feedback','checkoutcome');
								$categoryContent .= '<br><span>';
									//$categoryContent .= '<span style="background-color: #5491EE;color:#000;border: 1px solid #000000;font-style: italic;">'.$item[3]->feedback.'</span>';
									$categoryContent .= '<span style="border: 1px solid #000000;font-style: italic;">'.$item[3]->comment.'</span>';
									$feedbackhisto = $this->getFeedbackHisto($item[3]);
									$categoryContent .= ' <span style="font-size:70%;">['.$this->date_fr('d-M-Y H:i',$feedbackhisto->timecreated).']</span>';
								$categoryContent .= '</span>';
							$categoryContent .= '</span>';
						}							
					$categoryContent .= '</td>';
					// Print counter
					$categoryContent .= '<td style="text-align:center;">';
						if ($item[1]->countgoal != 0) {
							$count = 0;
							if ($item[2] != null) {
								$count = $item[2]->count;
							}
							$categoryContent .= '<span>'.$count.'</span>';
							$categoryContent .='<span>/'.$item[1]->countgoal.'</span>';
						}
					$categoryContent .= '</td>';
					// End Print counter					
					//Print student selfgrade
						// get scaleitems
						$scale = $DB->get_record('scale', array('id' => $item[0]->scaleid));
						$scaleitems = mb_split(',',$scale->scale);
					if ($item[2] != null && $item[2]->grade != null) {
						$categoryContent .= '<td valign="middle" style="text-align:center;vertical-align:middle;">'.$scaleitems[$item[2]->grade -1].'</td>';
					} else {
						$categoryContent .= '<td valign="middle" style="text-align:center;vertical-align:middle;"> - </td>';
					}					
					//Print teacher grade
					if ($item[3] != null && $item[3]->grade != null) {
						$categoryContent .= '<td valign="middle" style="text-align:center;vertical-align:middle;">'.$scaleitems[$item[3]->grade -1].'</td>';
					} else {
						$categoryContent .= '<td valign="middle" style="text-align:center;vertical-align:middle;"> - </td>';
					}					
				$categoryContent .= '</tr>';						
			}			
			$categoryContent .= '</table>';
			$pdf->writeHTML($categoryContent, true, false, false, false, '');
			
		} else {
			$categoryContent .= get_string('empty_list','checkoutcome');
			$pdf->writeHTML($categoryContent, true, false, false, false, '');
		}
				
		//Close and output PDF document
		$pdf->Output('doc.pdf', 'D');
    	   
}    
    
    /**
     * Local shortcut function for creating a link to a scale.
     * @param int $courseid The Course ID
     * @param grade_scale $scale The Scale to link to
     * @param grade_plugin_return $gpr An object used to identify the page we just came from
     * @return string html
     */
    function grade_print_scale_link($courseid, $scale, $gpr) {
    	global $CFG, $OUTPUT;
    	$url = new moodle_url('/grade/edit/scale/edit.php', array('courseid' => $courseid, 'id' => $scale->id));
    	$url = $gpr->add_url_params($url);
    	return html_writer::link($url, $scale->name);
    }
    
    function checkAffectedGradeItems() {
    	global $DB;
    	// Get the list of duplicated gradeitem attribute in checkoutcome items table
    	$sql = 'SELECT gradeitem, COUNT(gradeitem) AS dup_count , MAX(timecreated) AS maxtime FROM {checkoutcome_item} GROUP BY gradeitem HAVING (COUNT(gradeitem) > 1)';
    	$duplicates = $DB->get_records_sql($sql);
       	
    	// update duplicates with new gradeitems
    	foreach ($duplicates as $dup) {
    		if (!$old_gradeitem = $DB->get_record('grade_items', array('id' => $dup->gradeitem))) {
    			continue;
    		} else {
    			//Get gradeitems with the same itemname
    			$sql = 'SELECT MAX(gi.id) AS val FROM {grade_items} AS gi, {grade_outcomes} AS go WHERE gi.itemname= ? AND gi.outcomeid = go.id';
    			$gradeitemid = $DB->get_record_sql($sql,array($old_gradeitem->itemname));    			
    			
    			// Get checkoutcome item to be updated
    			$sql = 'SELECT * FROM {checkoutcome_item} WHERE gradeitem = ? ORDER BY id DESC';
    			$ch_items = $DB->get_records_sql($sql, array($dup->gradeitem));
    			$chid = null;
    			foreach ($ch_items as $ch) {
    				$chid = $ch->id;
    				break;
    			}
    			// Update it
    			$ch_items[$chid]->gradeitem = $gradeitemid->val;
    			$DB->update_record('checkoutcome_item', $ch_items[$chid]);		
    		}
    		
    		
    		
    	}
    	
    }
    
    
    function isPreview() {
    	return ($this->studentid == null && !$this->canupdateown());
    }
    
    function isGradingByStudent() {
    	return ($this->studentid == null && $this->canupdateown());
    }
    
    function isGradingByTeacher() {
    	//return ($this->studentid != null && !$this->canupdateown());
		return ($this->studentid != null && $this->canupdateother());
    }
    
   	function teacherHasGraded($item) {
    	if ($item[3] != null && $item[3]->grade != null && $item[3]->grade != 0) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
    function canupdateown() {
    	global $USER;
    	return (!$this->userid || ($this->userid == $USER->id)) && has_capability('mod/checkoutcome:updateown', $this->context);
    }
    
    function canaddown() {
    	global $USER;
    	return $this->checkoutcome->useritemsallowed && (!$this->userid || ($this->userid == $USER->id)) && has_capability('mod/checkoutcome:updateown', $this->context);
    }
    
    function canpreview() {
    	return has_capability('mod/checkoutcome:preview', $this->context);
    }
    
    function canedit() {
    	return has_capability('mod/checkoutcome:edit', $this->context);
    }
    
    function canupdateother() {
    	return has_capability('mod/checkoutcome:updateother', $this->context);
    }
    
    function canviewreports() {
    	return has_capability('mod/checkoutcome:viewreports', $this->context) || has_capability('mod/checkoutcome:viewmenteereports', $this->context);
    }
    
    function canviewallgroups() {
    	return has_capability('moodle/site:accessallgroups', $this->context) && has_capability('mod/checkoutcome:viewallcoursegroups', $this->context);
    }
   
 
}
