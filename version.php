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
 * Defines the version of checkoutcome
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!isset($plugin)) {
    // Avoid warning message in M2.5 and below.
    $plugin = new stdClass();
}
// Used by M2.6 and above.
$plugin->version  = 2014102802;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2010031900;
$plugin->component = 'mod_checkoutcome';
$plugin->cron     = 0;          // Period for cron to check this module (secs)
$plugin->maturity = MATURITY_STABLE;
$plugin->release  = '1.4.2 (Fork by JF from CheckOutcome 1.3.2)';
$plugin->dependencies = NULL;

if (!isset($module)) {
    // Avoid warning message when $module support is dropped.
    $module = new stdClass();
}
// Used by M2.5 and below.
$module->version = $plugin->version;
$module->requires = $plugin->requires;
$module->component = $plugin->component;
$module->cron = $plugin->cron;
$module->maturity = $plugin->maturity;
$module->release = $plugin->release;
$module->dependencies = $plugin->dependencies;


