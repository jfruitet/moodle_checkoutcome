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
 * Define all the backup steps that will be used by the backup_checkoutcome_activity_task
 */

/**
 * Define the complete checkoutcome structure for backup, with file and id annotations
 */
class backup_checkoutcome_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $checkoutcome = new backup_nested_element('checkoutcome', array('id'), array(
            'course', 'name', 'intro', 'introformat','timecreated', 'timemodified'));       

        $displays = new backup_nested_element('displays');

        $display = new backup_nested_element('display', array('id'), array(
            'checkoutcome', 'name', 'description', 'color',
            'iswhitefont', 'timecreated', 'timemodified'));

        $categories = new backup_nested_element('categories');

        $category = new backup_nested_element('category', array('id'), array(
            'checkoutcome', 'shortname', 'name', 'description', 'timecreated', 'timemodified'));

        $periods = new backup_nested_element('periods');

        $period = new backup_nested_element('period', array('id'), array(
            'checkoutcome','shortname','name','lockperiod','description','startdate','enddate','timecreated','timemodified'));
        
        $items = new backup_nested_element('items');
        
        $item = new backup_nested_element('item', array('id'), array(
        		'checkoutcome', 'itemnumber', 'gradeitem', 'scaleid',
        		'countgoal', 'display', 'category', 'timecreated',
        		'timemodified','resource'));

        $selfgradings = new backup_nested_element('selfgradings');

        $selfgrading = new backup_nested_element('selfgrading', array('id'), array(
            'checkoutcomeitem', 'userid', 'period','grade','count','counttime','comment','commenttime','timecreated', 'timemodified'));

        $selfgrad_histos = new backup_nested_element('selfgrad_histos');
        
        $selfgrad_histo = new backup_nested_element('selfgrad_histo', array('id'), array(
        		'oldid','action','source','grade','count','counttime','comment','commenttime','timecreated'));
        
        $teachergradings = new backup_nested_element('teachergradings');
        
        $teachergrading = new backup_nested_element('teachergrading', array('id'), array(
        		'checkoutcomeitem', 'userid', 'period','grade','usermodified','comment','commenttime','timecreated', 'timemodified'));
        
        $teachgrad_histos = new backup_nested_element('teachgrad_histos');
        
        $teachgrad_histo = new backup_nested_element('teachgrad_histo', array('id'), array(
        		'oldid','action','source','grade','comment','commenttime','timecreated','usermodified'));
        
        $documents_self = new backup_nested_element('documents_self');
        $documents_teach = new backup_nested_element('documents_teach');
        
        $document_self = new backup_nested_element('document_self', array('id'), array(
        		'gradeid', 'description', 'url','fileid','title','timecreated', 'timemodified'));
        $document_teach = new backup_nested_element('document_teach', array('id'), array(
        		'gradeid', 'description', 'url','fileid','title','timecreated', 'timemodified'));
        
        $period_goals = new backup_nested_element('period_goals');
        
        $period_goal = new backup_nested_element('period_goal', array('id'), array(
        		'period', 'userid', 'usermodified','goal','appraisal','studentsdescription','timecreated', 'timemodified'));
        
        // Build the tree
        
        $checkoutcome->add_child($displays);
        $displays->add_child($display);
        
        $checkoutcome->add_child($categories);
        $categories->add_child($category);
        
        $checkoutcome->add_child($periods);
        $periods->add_child($period);
        $period->add_child($period_goal);

        $checkoutcome->add_child($items);
        $items->add_child($item);        
                
        $item->add_child($selfgradings);
        $selfgradings->add_child($selfgrading);
        $item->add_child($teachergradings);
        $teachergradings->add_child($teachergrading);       

       	$selfgrading->add_child($documents_self);
       	$documents_self->add_child($document_self);

       	$teachergrading->add_child($documents_teach);
       	$documents_teach->add_child($document_teach);       	
       	
       	$selfgrading->add_child($selfgrad_histos);
       	$selfgrad_histos->add_child($selfgrad_histo);
       	
       	$teachergrading->add_child($teachgrad_histos);
       	$teachgrad_histos->add_child($teachgrad_histo);

        // Define sources

        $checkoutcome->set_source_table('checkoutcome', array('id' => backup::VAR_ACTIVITYID));
        
        $item->set_source_table('checkoutcome_item', array('checkoutcome' => backup::VAR_PARENTID));
        $display->set_source_table('checkoutcome_display', array('checkoutcome' => backup::VAR_PARENTID));
        $category->set_source_table('checkoutcome_category', array('checkoutcome' => backup::VAR_PARENTID));
        $period->set_source_table('checkoutcome_periods', array('checkoutcome' => backup::VAR_PARENTID));        
         

        // All these source definitions only happen if we are including user info
        if ($userinfo) {
            
        	$period_goal->set_source_table('checkoutcome_period_goals', array('period' => backup::VAR_PARENTID));
        	
        	$selfgrading->set_source_table('checkoutcome_selfgrading', array('checkoutcomeitem' => backup::VAR_PARENTID));
        	
        	$teachergrading->set_source_table('checkoutcome_teachergrading', array('checkoutcomeitem' => backup::VAR_PARENTID));
        	
        	$document_self->set_source_table('checkoutcome_document', array('gradeid' => backup::VAR_PARENTID));
        	$document_teach->set_source_table('checkoutcome_document', array('gradeid' => backup::VAR_PARENTID));
        	
        	$selfgrad_histo->set_source_table('checkoutcome_selfgrad_histo', array('oldid' => backup::VAR_PARENTID));
        	
        	$teachgrad_histo->set_source_table('checkoutcome_teachgrad_histo', array('oldid' => backup::VAR_PARENTID));        	
       
        }

        // Define id annotations
        
        $checkoutcome->annotate_ids('course', 'course');
        
        $item->annotate_ids('scale', 'scaleid');
        $item->annotate_ids('grade_items', 'gradeitem');

        $selfgrading->annotate_ids('user', 'userid');
        
        $teachergrading->annotate_ids('user', 'userid');
        $teachergrading->annotate_ids('user', 'usermodified');
        
        $period_goal->annotate_ids('user', 'userid');
        $period_goal->annotate_ids('user', 'usermodified');
        
        $document_self->annotate_ids('files', 'fileid');
        $document_teach->annotate_ids('files', 'fileid');
        
        
        // Define file annotations

        $document_self->annotate_files('mod_checkoutcome', 'document', 'id');
        $document_teach->annotate_files('mod_checkoutcome', 'document', 'id');

        // Return the root element (forum), wrapped into standard activity structure
        return $this->prepare_activity_structure($checkoutcome);
    }

}
