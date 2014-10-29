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
 * Capability definitions for the checkoutcome module
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
 * The variable name for the capability definitions array is $capabilities
 *
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

// Ability to view and update own checkoutcome
      'mod/checkoutcome:updateown' => array(
          'riskbitmask' => RISK_SPAM,
          'captype' => 'write',
          'contextlevel' => CONTEXT_MODULE,
          'legacy' => array(
              'student' => CAP_ALLOW
          )
      ),

      // Ability to alter the marks on another person's checkoutcome
      'mod/checkoutcome:updateother' => array(
          'riskbitmask' => RISK_PERSONAL | RISK_SPAM,
          'captype' => 'write',
          'contextlevel' => CONTEXT_MODULE,
          'legacy' => array(
              'teacher' => CAP_ALLOW,
              'editingteacher' => CAP_ALLOW,
              'manager' => CAP_ALLOW
          )
      ),

      // Ability to preview a checkoutcome (to check it is OK)
      'mod/checkoutcome:preview' => array(
          'captype' => 'read',
          'contextlevel' => CONTEXT_MODULE,
          'legacy' => array(
			  'student' => CAP_ALLOW,//
              'teacher' => CAP_ALLOW,
              'editingteacher' => CAP_ALLOW,
              'manager' => CAP_ALLOW
          )
      ),

      // Ability to check up on the progress of all users through
      // their checkoutcomes
      'mod/checkoutcome:viewreports' => array(
          'riskbitmask' => RISK_PERSONAL,
          'captype' => 'read',
          'contextlevel' => CONTEXT_MODULE,
          'legacy' => array(
			  'student' => CAP_ALLOW,//
              'teacher' => CAP_ALLOW,
              'editingteacher' => CAP_ALLOW,
              'manager' => CAP_ALLOW
          )
      ),

      // Ability to view reports related to their 'mentees' only
      'mod/checkoutcome:viewmenteereports' => array(
          'riskbitmask' => RISK_PERSONAL,
          'captype' => 'read',
          'contextlevel' => CONTEXT_MODULE,
          'legacy' => array(
			   'student' => CAP_ALLOW//
		  )  
      ),

      // Ability to create and manage checkoutcomes
      'mod/checkoutcome:edit' => array(
          'riskbitmask' => RISK_SPAM,
          'captype' => 'write',
          'contextlevel' => CONTEXT_MODULE,
          'legacy' => array(
              //'editingteacher' => CAP_ALLOW,
              'manager' => CAP_ALLOW,
			  'teacher' => CAP_ALLOW//
          )
      ),

      // Will receive emails when checkoutcomes complete (if checkoutcome is set to do so)
      'mod/checkoutcome:emailoncomplete' => array(
           'riskbitmask' => RISK_PERSONAL,
           'captype' => 'read',
           'contextlevel' => CONTEXT_MODULE,
           'legacy' => array(
              'editingteacher' => CAP_ALLOW,
              'teacher' => CAP_ALLOW
           )
      ),

      // Can update teacher checkoutcome marks even if locked
      'mod/checkoutcome:updatelocked' => array(
           'captype' => 'write',
           'contextlevel' => CONTEXT_MODULE,
           'legacy' => array(
              'editingteacher' => CAP_ALLOW,
              'manager' => CAP_ALLOW
           )
      ),
	 
		// Can update teacher checkoutcome add new instance
		'mod/checkoutcome:addinstance' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_MODULE,
				'legacy' => array(
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				)
		),
		
		// Can update teacher checkoutcome add new instance
		'mod/checkoutcome:viewallcoursegroups' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_MODULE,
				'legacy' => array(
						//'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				)
		)
);

