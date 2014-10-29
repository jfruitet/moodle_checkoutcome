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
 * This file keeps track of upgrades to the checkoutcome module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute checkoutcome upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_checkoutcome_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // Upgrade from 30th of october 2012
    if ($oldversion < 2012103000) {

        /////////////////////////////////////////////////////////////
        // Update table checkoutcome_selfgrading
        /////////////////////////////////////////////////////////////
    	
    	
    	// Define field course to be added to checkoutcome_selfgrading
        $table_selfgrading = new xmldb_table('checkoutcome_selfgrading');
        $field_period = new xmldb_field('period', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
        $field_count = new xmldb_field('count', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, false, null, '0', 'grade');
        $field_counttime = new xmldb_field('counttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, '0', 'count');

        // Add fields
        if (!$dbman->field_exists($table_selfgrading, $field_period)) {
            $dbman->add_field($table_selfgrading, $field_period);
        }
        if (!$dbman->field_exists($table_selfgrading, $field_count)) {
        	$dbman->add_field($table_selfgrading, $field_count);
        }
        if (!$dbman->field_exists($table_selfgrading, $field_counttime)) {
        	$dbman->add_field($table_selfgrading, $field_counttime);
        }
        
        /////////////////////////////////////////////////////////////
        // Update table checkoutcome_item
        /////////////////////////////////////////////////////////////
         
         
        // Define field to be added to checkoutcome_item
        $table_item = new xmldb_table('checkoutcome_item');
        $field_countgoal = new xmldb_field('countgoal', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, false, null, '0', 'scaleid');
        
        // Add field
        if (!$dbman->field_exists($table_item, $field_countgoal)) {
        	$dbman->add_field($table_item, $field_countgoal);
        }
        
        /////////////////////////////////////////////////////////////
        // Update table checkoutcome_document
        /////////////////////////////////////////////////////////////
         
         
        // Define field to be renamed to checkoutcome_document
        $table_doc = new xmldb_table('checkoutcome_document');
        $field_selfgradingid = new xmldb_field('selfgradingid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        
        // Rename field
        if ($dbman->field_exists($table_doc, $field_selfgradingid)) {
        	$dbman->rename_field($table_doc, $field_selfgradingid, 'gradeid');
        }
        
        /////////////////////////////////////////////////////////////
        // Create table checkoutcome_periods
        /////////////////////////////////////////////////////////////         
         
        // Define table checkoutcome_periods
        $table_periods = new xmldb_table('checkoutcome_periods');
        
        $table_periods->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table_periods->add_field('checkoutcome', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        $table_periods->add_field('name', XMLDB_TYPE_TEXT, '250', null, XMLDB_NOTNULL, null, '0', 'checkoutcome');
        $table_periods->add_field('description', XMLDB_TYPE_TEXT, '1000', null, false, null, '0', 'name');
        $table_periods->add_field('startdate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'description');
        $table_periods->add_field('enddate', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'startdate');
        $table_periods->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'enddate');
        $table_periods->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated');
        // Add primary key
        $table_periods->add_key('checkoutcome_periods_primkey', XMLDB_KEY_PRIMARY, array('id'));
        // index on checkoutcome
        $table_periods->add_index('checkoutcome_per_check_index', XMLDB_INDEX_NOTUNIQUE, array('checkoutcome'));
        
        // Add table
        if (!$dbman->table_exists($table_periods)) {
        	$dbman->create_table($table_periods);
        }        
        
        /////////////////////////////////////////////////////////////
        // Create table checkoutcome_periods_goals
        /////////////////////////////////////////////////////////////
         
         
        // Define table checkoutcome_periods_goals
        $table_period_goals = new xmldb_table('checkoutcome_period_goals');
        $table_period_goals->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table_period_goals->add_field('period', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        $table_period_goals->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'period');
        $table_period_goals->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
        $table_period_goals->add_field('goal', XMLDB_TYPE_TEXT, '1000', null, false, null, '0', 'usermodified');
        $table_period_goals->add_field('appraisal', XMLDB_TYPE_TEXT, '1000', null, false, null, '0', 'goal');
        $table_period_goals->add_field('studentsdescription', XMLDB_TYPE_TEXT, '1000', null, false, null, '0', 'appraisal');
        $table_period_goals->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'studentsdescription');
        $table_period_goals->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated');
        // Add primary key
        $table_period_goals->add_key('checkoutcome_period_goals_primkey', XMLDB_KEY_PRIMARY, array('id'));
        // Add index on period
        $table_period_goals->add_index('checkoutcome_per_g_per_index', XMLDB_INDEX_NOTUNIQUE, array('period'));
        // Add table
        if (!$dbman->table_exists($table_period_goals)) {
        	$dbman->create_table($table_period_goals);
        }
      
        /////////////////////////////////////////////////////////////
        // Create table checkoutcome_teachergrading
        /////////////////////////////////////////////////////////////         
         
        // Define table checkoutcome_teachergrading
        $table_teachergrading = new xmldb_table('checkoutcome_teachergrading');        
        
        $table_teachergrading->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table_teachergrading->add_field('checkoutcomeitem', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        $table_teachergrading->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'checkoutcomeitem');
        $table_teachergrading->add_field('period', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
        $table_teachergrading->add_field('grade', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, false, null, '0', 'period');
        $table_teachergrading->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, '0', 'grade');
        $table_teachergrading->add_field('comment', XMLDB_TYPE_TEXT, '1000', null, false, null, '0', 'usermodified');
        $table_teachergrading->add_field('commenttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, '0', 'comment');
        $table_teachergrading->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'commenttime');
        $table_teachergrading->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated');
        // Add primary key
        $table_teachergrading->add_key('checkoutcome_tgrad_primkey', XMLDB_KEY_PRIMARY, array('id'));
        //Add index on checkoutcomeitem
        $table_teachergrading->add_index('checkoutcome_tgrad_chitem_index', XMLDB_INDEX_NOTUNIQUE, array('checkoutcomeitem'));
        // Add table
        if (!$dbman->table_exists($table_teachergrading)) {
        	$dbman->create_table($table_teachergrading);
        }
       
        /////////////////////////////////////////////////////////////
        // Create table checkoutcome_teachergrading_history
        /////////////////////////////////////////////////////////////
         
         
        // Define table checkoutcome_teachergrading_history
        $table_teachergrading_histo = new xmldb_table('checkoutcome_teachgrad_histo');
        
        $table_teachergrading_histo->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table_teachergrading_histo->add_field('oldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        $table_teachergrading_histo->add_field('action', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'oldid');
        $table_teachergrading_histo->add_field('source', XMLDB_TYPE_TEXT, '20', null, false, null, '0', 'action');        
        $table_teachergrading_histo->add_field('grade', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'source');
        $table_teachergrading_histo->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'grade');
        $table_teachergrading_histo->add_field('comment', XMLDB_TYPE_TEXT, '1000', null, false, null, '0', 'usermodified');
        $table_teachergrading_histo->add_field('commenttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, '0', 'comment');
        $table_teachergrading_histo->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'commenttime');
        // Add primary key
        $table_teachergrading_histo->add_key('checkoutcome_tgrad_h_primkey', XMLDB_KEY_PRIMARY, array('id'));
        // Add index on oldid
        $table_teachergrading_histo->add_index('checkoutcome_tgrad_h_oldid_index', XMLDB_INDEX_NOTUNIQUE, array('oldid'));
        // Add table
        if (!$dbman->table_exists($table_teachergrading_histo)) {
        	$dbman->create_table($table_teachergrading_histo);
        }
               
        /////////////////////////////////////////////////////////////
        // Create table checkoutcome_selfgrading_history
        /////////////////////////////////////////////////////////////
         
         
        // Define table checkoutcome_selfgrading_history
        $table_selfgrading_histo = new xmldb_table('checkoutcome_selfgrad_histo');
        
        $table_selfgrading_histo->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table_selfgrading_histo->add_field('oldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
        $table_selfgrading_histo->add_field('action', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'oldid');
        $table_selfgrading_histo->add_field('source', XMLDB_TYPE_TEXT, '20', null, false, null, '0', 'action');
        $table_selfgrading_histo->add_field('grade', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'source');
        $table_selfgrading_histo->add_field('count', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, false, null, '0', 'grade');
        $table_selfgrading_histo->add_field('counttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, '0', 'count');
        $table_selfgrading_histo->add_field('comment', XMLDB_TYPE_TEXT, '1000', null, false, null, '0', 'counttime');
        $table_selfgrading_histo->add_field('commenttime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, false, null, '0', 'comment');
        $table_selfgrading_histo->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'commenttime');
        // Add primary key
        $table_selfgrading_histo->add_key('checkoutcome_sgrad_h_primkey', XMLDB_KEY_PRIMARY, array('id'));
        // Add index on oldid
        $table_selfgrading_histo->add_index('checkoutcome_sgrad_h_oldid_index', XMLDB_INDEX_NOTUNIQUE, array('oldid'));
        // Add table
        if (!$dbman->table_exists($table_selfgrading_histo)) {
        	$dbman->create_table($table_selfgrading_histo);
        }        
             
        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2007040100 so the next time this block is skipped
        upgrade_mod_savepoint(true, 2012103000, 'checkoutcome');
        
    }
    
    // Upgrade from 15th of november 2012
    if ($oldversion < 2012111500) {
    
    	/////////////////////////////////////////////////////////////
    	// Update table checkoutcome_item
    	/////////////////////////////////////////////////////////////
    	 
    	 
    	// Define field resource to be added to checkoutcome_item
    	$table_item = new xmldb_table('checkoutcome_item');
    	$field_resource = new xmldb_field('resource', XMLDB_TYPE_TEXT, '500', null, false, null, '0', 'countgoal');
    	
    	// Add fields
    	if (!$dbman->field_exists($table_item, $field_resource)) {
    		$dbman->add_field($table_item, $field_resource);
    	}    	
    	
    	upgrade_mod_savepoint(true, 2013010200, 'checkoutcome');
    }
	
	// Upgrade from 17th of january 2013
    if ($oldversion <= 2013011700) 
	{
		/////////////////////////////////////////////////////////////
        // Update table checkoutcome_category
        /////////////////////////////////////////////////////////////
         
         
        // Define field to be added to checkoutcome_category
        $table_category = new xmldb_table('checkoutcome_category');
        $field_shortname = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0', 'checkoutcome');
        
        // Add field
        if (!$dbman->field_exists($table_category, $field_shortname)) {
        	$dbman->add_field($table_category, $field_shortname);
        }
		
		/////////////////////////////////////////////////////////////
        // Update table checkoutcome_periods
        /////////////////////////////////////////////////////////////
         
         
        // Define field to be added to checkoutcome_periods
        $table_periods = new xmldb_table('checkoutcome_periods');
        $field_shortname = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '0', 'checkoutcome');
		$field_lockperiod = new xmldb_field('lockperiod', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'name');
		$field_startdate = new xmldb_field('startdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'description');
		$field_enddate = new xmldb_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'startdate');
        
        // Add field
        if (!$dbman->field_exists($table_periods, $field_shortname)) {
        	$dbman->add_field($table_periods, $field_shortname);
        }
		if (!$dbman->field_exists($table_periods, $field_lockperiod)) {
        	$dbman->add_field($table_periods, $field_lockperiod);
        }
		// update field
        if ($dbman->field_exists($table_periods, $field_startdate)) {
        	$dbman->change_field_notnull($table_periods, $field_startdate);
        }
        if ($dbman->field_exists($table_periods, $field_enddate)) {
        	$dbman->change_field_notnull($table_periods, $field_enddate);
        }
		
		/////////////////////////////////////////////////////////////
        // Update table checkoutcome_teachergrading
        /////////////////////////////////////////////////////////////
         
         
        // Define field to be added to checkoutcome_periods
        $table_teachergrading = new xmldb_table('checkoutcome_teachergrading');
        $field_grade = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'period');
		$field_usermodified = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'grade');
        
        // update field
        if ($dbman->field_exists($table_teachergrading, $field_grade)) {
        	$dbman->change_field_notnull($table_teachergrading, $field_grade);
        }
		// update field
        if ($dbman->field_exists($table_teachergrading, $field_usermodified)) {
        	$dbman->change_field_notnull($table_teachergrading, $field_usermodified);
        }
		
		/////////////////////////////////////////////////////////////
        // Update table role_capabilities
        /////////////////////////////////////////////////////////////
		
		$new_role_capabilities_1 = new stdClass();
    	$new_role_capabilities_1->contextid = 1;
    	$new_role_capabilities_1->roleid = 5;
    	$new_role_capabilities_1->capability = 'mod/checkoutcome:preview';
    	$new_role_capabilities_1->permission = 1;
    	$new_role_capabilities_1->timemodified = time();
    	$new_role_capabilities_1->modifierid = 0;
    	$DB->insert_record('role_capabilities', $new_role_capabilities_1);
		
		$new_role_capabilities_2 = new stdClass();
    	$new_role_capabilities_2->contextid = 1;
    	$new_role_capabilities_2->roleid = 5;
    	$new_role_capabilities_2->capability = 'mod/checkoutcome:viewreports';
    	$new_role_capabilities_2->permission = 1;
    	$new_role_capabilities_2->timemodified = time();
    	$new_role_capabilities_2->modifierid = 0;
    	$DB->insert_record('role_capabilities', $new_role_capabilities_2);
		
		$new_role_capabilities_3 = new stdClass();
    	$new_role_capabilities_3->contextid = 1;
    	$new_role_capabilities_3->roleid = 5;
    	$new_role_capabilities_3->capability = 'mod/checkoutcome:viewmenteereports';
    	$new_role_capabilities_3->permission = 1;
    	$new_role_capabilities_3->timemodified = time();
    	$new_role_capabilities_3->modifierid = 0;
    	$DB->insert_record('role_capabilities', $new_role_capabilities_3);
		
		$role_capability_1 = $DB->get_record('role_capabilities', array('contextid' => 1, 'roleid' => 3, 'capability' => 'mod/checkoutcome:edit', 'permission' => 1));
		if ($role_capability_1) 
		{
			$role_capability_1->permission = -1;
			$DB->update_record('role_capabilities', $role_capability_1);
		}
		
		$role_capability_2 = $DB->get_record('role_capabilities', array('contextid' => 1, 'roleid' => 3, 'capability' => 'mod/checkoutcome:viewallcoursegroups', 'permission' => 1));
		if ($role_capability_2) 
		{
			$role_capability_2->permission = -1;
			$DB->update_record('role_capabilities', $role_capability_2);
		}
		
		
		upgrade_mod_savepoint(true, 2013041500, 'checkoutcome');
	}
	
	// Upgrade from 17th of january 2013
    if ($oldversion <= 2013041500) 
	{
		// Define field to be added to checkoutcome_periods
        $table_teachergrading_histo = new xmldb_table('checkoutcome_teachgrad_histo');
        $field_grade = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'source');
        
        // update field
        if ($dbman->field_exists($table_teachergrading_histo, $field_grade)) {
        	$dbman->change_field_notnull($table_teachergrading_histo, $field_grade);
        }
		
		
		$role_capability_1 = $DB->get_record('role_capabilities', array('contextid' => 1, 'roleid' => 3, 'capability' => 'mod/checkoutcome:edit', 'permission' => -1));
		if ($role_capability_1) 
		{
			$role_capability_1->permission = 1;
			$DB->update_record('role_capabilities', $role_capability_1);
		}
		
		upgrade_mod_savepoint(true, 2013041800, 'checkoutcome');
	}   
   

    return true;
}
