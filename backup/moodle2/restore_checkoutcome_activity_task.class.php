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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/checkoutcome/backup/moodle2/restore_checkoutcome_stepslib.php'); // Because it exists (must)

/**
 * checkoutcome restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_checkoutcome_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_checkoutcome_activity_structure_step('checkoutcome_structure', 'checkoutcome.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('checkoutcome', array('intro'), 'checkoutcome');
        $contents[] = new restore_decode_content('checkoutcome_selfgrading', array('comment'), 'checkoutcome_selfgrading_comment');
        $contents[] = new restore_decode_content('checkoutcome_teachergrading', array('comment'), 'checkoutcome_teachergrading_comment');
        $contents[] = new restore_decode_content('checkoutcome_period_goals', array('goal','appraisal','studentsdescription'), 'period_goal');
        $contents[] = new restore_decode_content('checkoutcome_display', array('description'), 'display');
        $contents[] = new restore_decode_content('checkoutcome_category', array('description'), 'category');
        $contents[] = new restore_decode_content('checkoutcome_periods', array('description'), 'period');
        $contents[] = new restore_decode_content('checkoutcome_document', array('description'), 'document');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        // List of checkoutcomes in course
        $rules[] = new restore_decode_rule('CHECKOUTCOMEINDEX', '/mod/checkoutcome/index.php?id=$1', 'course');
        // Forum by cm->id and forum->id
        $rules[] = new restore_decode_rule('CHECKOUTCOMEVIEWBYID', '/mod/checkoutcome/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKOUTCOMEVIEWBYF', '/mod/checkoutcome/view.php?f=$1', 'checkoutcome');
        
        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * forum logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('forum', 'add', 'view.php?id={course_module}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'update', 'view.php?id={course_module}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'view', 'view.php?id={course_module}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'view forum', 'view.php?id={course_module}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'mark read', 'view.php?f={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'start tracking', 'view.php?f={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'stop tracking', 'view.php?f={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'subscribe', 'view.php?f={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'unsubscribe', 'view.php?f={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'subscriber', 'subscribers.php?id={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'subscribers', 'subscribers.php?id={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'view subscribers', 'subscribers.php?id={forum}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'add discussion', 'discuss.php?d={forum_discussion}', '{forum_discussion}');
        $rules[] = new restore_log_rule('forum', 'view discussion', 'discuss.php?d={forum_discussion}', '{forum_discussion}');
        $rules[] = new restore_log_rule('forum', 'move discussion', 'discuss.php?d={forum_discussion}', '{forum_discussion}');
        $rules[] = new restore_log_rule('forum', 'delete discussi', 'view.php?id={course_module}', '{forum}',
                                        null, 'delete discussion');
        $rules[] = new restore_log_rule('forum', 'delete discussion', 'view.php?id={course_module}', '{forum}');
        $rules[] = new restore_log_rule('forum', 'add post', 'discuss.php?d={forum_discussion}&parent={forum_post}', '{forum_post}');
        $rules[] = new restore_log_rule('forum', 'update post', 'discuss.php?d={forum_discussion}#p{forum_post}&parent={forum_post}', '{forum_post}');
        $rules[] = new restore_log_rule('forum', 'prune post', 'discuss.php?d={forum_discussion}', '{forum_post}');
        $rules[] = new restore_log_rule('forum', 'delete post', 'discuss.php?d={forum_discussion}', '[post]');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('forum', 'view forums', 'index.php?id={course}', null);
        $rules[] = new restore_log_rule('forum', 'subscribeall', 'index.php?id={course}', '{course}');
        $rules[] = new restore_log_rule('forum', 'unsubscribeall', 'index.php?id={course}', '{course}');
        $rules[] = new restore_log_rule('forum', 'user report', 'user.php?course={course}&id={user}&mode=[mode]', '{user}');
        $rules[] = new restore_log_rule('forum', 'search', 'search.php?id={course}&search=[searchenc]', '[search]');

        return $rules;
    }
}
