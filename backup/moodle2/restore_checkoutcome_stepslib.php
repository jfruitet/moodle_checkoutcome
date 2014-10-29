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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_checkoutcome_activity_task
 */

/**
 * Structure step to restore one checkoutcome activity
 */
class restore_checkoutcome_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('checkoutcome', '/activity/checkoutcome');
        $paths[] = new restore_path_element('checkoutcome_display', '/activity/checkoutcome/displays/display');
        $paths[] = new restore_path_element('checkoutcome_category', '/activity/checkoutcome/categories/category');
        $paths[] = new restore_path_element('checkoutcome_period', '/activity/checkoutcome/periods/period');
        $paths[] = new restore_path_element('checkoutcome_item', '/activity/checkoutcome/items/item');
        
        
        if ($userinfo) {
            $paths[] = new restore_path_element('checkoutcome_selfgrading', '/activity/checkoutcome/items/item/selfgradings/selfgrading');
            $paths[] = new restore_path_element('checkoutcome_teachergrading', '/activity/checkoutcome/items/item/teachergradings/teachergrading');
            $paths[] = new restore_path_element('checkoutcome_period_goal', '/activity/checkoutcome/periods/period/period_goals/period_goal');
            $paths[] = new restore_path_element('checkoutcome_document_self', '/activity/checkoutcome/items/item/selfgradings/selfgrading/documents_self/document_self');
            $paths[] = new restore_path_element('checkoutcome_document_teach', '/activity/checkoutcome/items/item/teachergradings/teachergrading/documents_teach/document_teach');
            $paths[] = new restore_path_element('checkoutcome_selfgrad_histo', '/activity/checkoutcome/items/item/selfgradings/selfgrading/selfgrad_histos/selfgrad_histo');
            $paths[] = new restore_path_element('checkoutcome_teachgrad_histo', '/activity/checkoutcome/items/item/teachergradings/teachergrading/teachgrad_histos/teachgrad_histo');           
            
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_checkoutcome($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('checkoutcome', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_checkoutcome_item($data) {
        global $DB;


        $data = (object)$data;
        $oldid = $data->id;        
        
        $data->checkoutcome = $this->get_new_parentid('checkoutcome');
        $data->scaleid = $this->get_mappingid('scale', $data->scaleid);
        $data->display = $this->get_mappingid('checkoutcome_display', $data->display);
        $data->category = $this->get_mappingid('checkoutcome_category', $data->category);
   		$data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('checkoutcome_item', $data);
        $this->set_mapping('checkoutcome_item', $oldid, $newitemid);
    }

    protected function process_checkoutcome_display($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->checkoutcome = $this->get_new_parentid('checkoutcome');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);      

        $newitemid = $DB->insert_record('checkoutcome_display', $data);
        $this->set_mapping('checkoutcome_display', $oldid, $newitemid);
      
    }
    
    protected function process_checkoutcome_category($data) {
    	global $DB;
    
    	$data = (object)$data;
    	$oldid = $data->id;
    
    	$data->checkoutcome = $this->get_new_parentid('checkoutcome');
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);
    
    	$newitemid = $DB->insert_record('checkoutcome_category', $data);
    	$this->set_mapping('checkoutcome_category', $oldid, $newitemid);
    	 
    }
    
    protected function process_checkoutcome_period($data) {
    	global $DB;
    
    	$data = (object)$data;
    	$oldid = $data->id;
    
    	$data->checkoutcome = $this->get_new_parentid('checkoutcome');
    	$data->startdate = $this->apply_date_offset($data->startdate);
    	$data->enddate = $this->apply_date_offset($data->enddate);    	
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);
    
    	$newitemid = $DB->insert_record('checkoutcome_periods', $data);
    	$this->set_mapping('checkoutcome_periods', $oldid, $newitemid);
    
    }

    protected function process_checkoutcome_selfgrading($data) {
        global $DB;

        $data = (object)$data;      
        $oldid = $data->id;
        
        $data->checkoutcomeitem = $this->get_new_parentid('checkoutcome_item');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->period = $this->get_mappingid('checkoutcome_periods', $data->period);
        $data->counttime = $this->apply_date_offset($data->counttime);
        $data->commenttime = $this->apply_date_offset($data->commenttime);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
       
        $newitemid = $DB->insert_record('checkoutcome_selfgrading', $data);
        $this->set_mapping('checkoutcome_selfgrading', $oldid, $newitemid);
    }
    
    protected function process_checkoutcome_teachergrading($data) {
    	global $DB;
    
    	$data = (object)$data;
    	$oldid = $data->id;
    
    	$data->checkoutcomeitem = $this->get_new_parentid('checkoutcome_item');
    	$data->userid = $this->get_mappingid('user', $data->userid);
    	$data->period = $this->get_mappingid('checkoutcome_periods', $data->period);
    	$data->usermodified = $this->get_mappingid('user', $data->usermodified);
    	$data->commenttime = $this->apply_date_offset($data->commenttime);
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);    
    	 
    	$newitemid = $DB->insert_record('checkoutcome_teachergrading', $data);
    	$this->set_mapping('checkoutcome_teachergrading', $oldid, $newitemid);
    }

	protected function process_checkoutcome_period_goal($data) {
    	global $DB;
    
    	$data = (object)$data;    
    	    	
    	$data->period = $this->get_new_parentid('checkoutcome_periods');
    	$data->userid = $this->get_mappingid('user', $data->userid);
    	$data->usermodified = $this->get_mappingid('user', $data->usermodified);
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);    
    	 
    	$newitemid = $DB->insert_record('checkoutcome_period_goals', $data);
    }

	protected function process_checkoutcome_document_self($data) {
    	global $DB;
    
    	$data = (object)$data;    
    	
    	$data->gradeid = $this->get_new_parentid('checkoutcome_selfgrading');
    	$data->fileid = $this->get_mappingid('files', $data->fileid);
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);    
    	 
    	$newitemid = $DB->insert_record('checkoutcome_document', $data);
    }
    
    protected function process_checkoutcome_document_teach($data) {
    	global $DB;
    
    	$data = (object)$data;
    
    	$data->gradeid = $this->get_new_parentid('checkoutcome_teachergrading');
    	$data->fileid = $this->get_mappingid('files', $data->fileid);
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);
    
    	$newitemid = $DB->insert_record('checkoutcome_document', $data);
    }
    
    protected function process_checkoutcome_selfgrad_histo($data) {
    	global $DB;
    
    	$data = (object)$data;
    
    	$data->oldid = $this->get_new_parentid('checkoutcome_selfgrading');
    	$data->counttime = $this->apply_date_offset($data->counttime);
    	$data->commenttime = $this->apply_date_offset($data->commenttime);
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    
    	$newitemid = $DB->insert_record('checkoutcome_selfgrad_histo', $data);
    }

	protected function process_checkoutcome_teachgrad_histo($data) {
    	global $DB;
    
    	$data = (object)$data;
    
    	$data->oldid = $this->get_new_parentid('checkoutcome_teachergrading');
    	$data->usermodified = $this->get_mappingid('user', $data->usermodified);    	 
    	$data->commenttime = $this->apply_date_offset($data->commenttime);
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    
    	$newitemid = $DB->insert_record('checkoutcome_teachgrad_histo', $data);
    }

    protected function after_execute() {
		$this->add_related_files('mod_checkoutcome', 'document', 'id');   	
    }
              
    
}
