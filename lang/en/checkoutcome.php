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
 * English strings for checkoutcome
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['language'] = 'En';
$string['modulename'] = 'Outcomes\' follow-up';
$string['modulenameplural'] = 'Outcomes\' follow-up';
$string['modulename_help'] = 'Use the Outcomes\' follow-up module for... | The Outcomes\' follow-up module allows...';
$string['checkoutcomefieldset'] = 'Custom example fieldset';
$string['checkoutcomename'] = 'Outcomes\' follow-up';
$string['checkoutcomename_help'] = 'This is the content of the help tooltip associated with the checkoutcomename field. Markdown syntax is supported.';
$string['checkoutcome'] = 'Outcomes\' follow-up';
$string['pluginadministration'] = 'Outcomes\' follow-up administration';
$string['pluginname'] = 'checkoutcome';
$string['empty_list'] = 'The outcomes\' list is empty.';
$string['empty_student_list'] = 'The students\' list is empty.';
$string['OK'] = 'OK';
$string['back'] = 'Back';
$string['backtolist'] = 'Back to the list';
$string['no_category_name'] = 'No group defined';
$string['no_category_desc'] = 'No group has been defined for these outcomes';
$string['validate'] = 'Validate';
$string['select_item'] = 'Select an item';
$string['teacher_grading'] = 'Grading by teacher';
$string['student_grading'] = 'Grading by student';
$string['save_grades'] = 'Save grades';
$string['lastdatestudent'] = 'Last change by student :';
$string['lastdateteacher'] = 'Last change by teacher :';
$string['page_refresh'] = 'Page refresh';
$string['export_pdf'] = 'Get a pdf file';
$string['bynameondate'] = 'by {$a->name} - {$a->date}';
$string['validatedbynameondate'] = 'graded by {$a->name} - {$a->date}';
$string['graded_item'] = 'Graded : ';
$string['maxlength'] = '(1000 characters max.)';
$string['finalgrade'] = 'Grade : ';
$string['view_details'] = 'View details about this outcome';
$string['add_student_description'] = 'Add a description of the period';
$string['edit_student_description'] = 'Edit description of the period';
$string['student_description'] = 'Description of the period';

// Tabs
$string['view'] = 'Self Grading';
$string['preview'] = 'Preview List';
$string['report'] = 'Report';
$string['edit'] = 'Outcomes';
$string['list_cat'] = 'Groups of outcomes';
$string['list_disp'] = 'Displays';
$string['list_gradings'] = 'Gradings';
$string['setting'] = 'Setting';
$string['summary'] = 'Summary';
$string['export'] = 'Export';
$string['list_period'] = 'Periods';

// Capabilities
$string['checkoutcome:addinstance'] = 'Create a new module';
$string['checkoutcome:edit'] = 'Edit the module';
$string['checkoutcome:updateown'] = 'Update one\'s own datas';
$string['checkoutcome:updateother'] = 'Update other people\'s datas';
$string['checkoutcome:preview'] = 'Preview';
$string['checkoutcome:viewreports'] = 'View module\'s reports';
$string['checkoutcome:emailoncomplete'] = 'Receive an email when student has completed the list';
$string['checkoutcome:updatelocked'] = 'Update locked datas';
$string['checkoutcome:viewmenteereports'] = 'View mentee reports';
$string['checkoutcome:viewallcoursegroups'] = 'View all groups in current course';

//Errors
$string['error_cmid'] = 'Course Module ID was incorrect';
$string['error_specif_id'] = 'You must specify a course_module ID or an instance ID';
$string['error_update'] = 'Error : you do not have permission to update this checklist';
$string['error_sesskey'] = 'Error : invalid session key';
$string['error_itemid'] = 'Error: invalid (or missing) items list';
$string['request_too_long'] = 'Possible reason : your selection was too large, try to reduce you selection';
$string['noteacherfound'] = 'No teacher found';
$string['nostudentfound'] = 'No student found';
$string['no_item_found'] = 'No item found';
$string['database_update_failed'] = 'Database update failed';

//Group
$string['add_category'] = 'Add a new group of outcomes';
$string['edit_category'] = 'Edit group';
$string['category_name'] = 'Name';
$string['category_description'] = 'Description';
$string['editcategory'] = 'Edit this group od outcomes';
$string['delete_category'] = 'Delete this group of outcomes';
$string['delete_category_confirm'] = 'Do you really want to delete this group of outcomes?';
$string['input_name_category'] = 'group of outcomes';
$string['category'] = 'Group of outcomes';
$string['category_help'] = 'In the frame of the Checkoutcome module, outcomes will be sorted by group';
$string['empty_category_list'] = 'The list of groups is empty.';

//Edit
$string['shortname'] = 'Short Name';
$string['fullname'] = 'Full Name';
$string['teacher_scale'] = 'Scale';
$string['student_scale'] = 'Student scale';
$string['type'] = 'Type';
$string['category_choice'] = 'Group of outcomes';
$string['display_choice'] = 'Display';
$string['sujet_normal'] = 'examen d\'un sujet normal';
$string['semio_desc'] = 'sémiologie descriptive';
$string['semio_syndro'] = 'sémiologie syndromique';
$string['raison_diag'] = 'raisonnement diagnostique';
$string['gestes_tech'] = 'gestes techniques';
$string['NA'] = 'NA';
$string['in_use'] = 'In use';
$string['edit_item'] = 'Edit';
$string['delete'] = 'Delete';
$string['update_outcomes'] = 'Update selected outcomes';
$string['update_outcomes_desc'] = 'Update selected outcomes with the selected category and display parameters';
$string['delete_outcomes'] = 'Delete selected outcomes';
$string['delete_outcomes_desc'] = 'Delete selected outcomes';
$string['link'] = 'Link';
$string['edit_link'] = 'Edit link';
$string['add_link'] = 'Add link';
$string['delete_link'] = 'Delete link';
$string['new_link_outcome'] = 'Add a link for outcome ';
$string['delete_link_outcome'] = 'Delete link for outcome ';
$string['delete_link_question'] = 'Do you really want to delete this link ?';
$string['counter'] = 'Counter';
$string['countergoal'] = 'Counter goal';

//Edit outcome
$string['outcome'] = 'Outcome';
$string['outcome_help'] = 'This outcome can be evaluated by the teacher and by the student himself. For the evaluation a scale will be proposed to the teacher, a different scale can be proposed to the student.';
$string['outcome_name'] = 'Name';
$string['edit_outcome'] = 'Edit outcome';
$string['delete_outcome_confirm'] = 'Do you really want to delete this outcome ?';
$string['add_outcome'] = 'Add new outcomes';
$string['add_outcome_desc'] = 'Add new outcomes by selecting in the list of available outcomes of the course';
$string['cancel'] = 'Cancel';
$string['deleteall_outcome'] = 'Delete all unused outcomes';
$string['deleteall_outcome_confirm'] = 'Do you really want to delete all outcomes ?\nOnly the outcomes that are not being used will be deleted.';
$string['delete_outcome_confirm'] = 'Do you really want to delete the selected outcomes ?\nOnly the outcomes that are not being used will be deleted.';

//Add outcome
$string['select_default_display'] = 'Default display : ';
$string['select_default_category'] = 'Default group : ';
$string['select_default_teacherscale'] = 'Default scale for teachers : ';
$string['select_default_studentscale'] = 'Default scale for students : ';
$string['enable_defaults'] = 'Define default values (display, group)';
$string['check_uncheck_all'] = 'Check/Uncheck all';

// Comment and document
$string['add_comment'] = 'Add a comment';
$string['edit_comment'] = 'Edit comment';
$string['delete_comment'] = 'Delete comment';
$string['add_document'] = 'Add a link to a document';
$string['delete_comment_confirm'] = 'Do you really want to delete this comment ?';
$string['input_name_document'] = 'document';
$string['document_help'] = 'This form will help you to add a link to a document';
$string['document_title'] = 'Title';
$string['document_description'] = 'Description';
$string['document'] = 'document';
$string['edit_document'] = 'Edit document';
$string['delete_document'] = 'Delete document';
$string['delete_document_confirm'] = 'Do you really want to delete this document ?';
$string['document_url_old'] = 'Current file';

// Display
$string['display'] = 'Display';
$string['display_name'] = 'Name';
$string['display_description'] = 'Description';
$string['edit_display'] = 'Edit display';
$string['delete_display'] = 'Delete display';
$string['delete_display_confirm'] = 'Do you really want to delete this display ?';
$string['add_display'] = 'Add a display';
$string['input_name_display'] = 'display';
$string['display_help'] = 'In the frame of the Checkoutcome module, competencies can be displayed by a different backgroundcolor in the list';
$string['color_choice'] = 'Background color';
$string['color_code'] = 'Background color code';
$string['is_white_font'] = 'Use white font color';
$string['empty_display_list'] = 'The list of displays is empty.';

// Period
$string['period'] = 'Period';
$string['lock'] = 'Locked period';
$string['markperiod'] = 'Reference period';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['update_periods'] = 'Update selected periods';
$string['update_periods_desc'] = 'Update selected periods with the selected locked periods and reference period';
$string['period_name'] = 'Name';
$string['period_description'] = 'Description';
$string['edit_period'] = 'Edit period';
$string['delete_period'] = 'Delete period';
$string['delete_period_confirm'] = 'Do you really want to delete this period ?';
$string['add_period'] = 'Add a period';
$string['input_name_period'] = 'period';
$string['period_help'] = 'The outcomes\'s follow-up can be managed over several time periods. The same list of outcomes is being used for each period, grades are independants from a period to another.';
$string['empty_period_list'] = 'The list of periods is empty.';
$string['dateyesno'] = 'Show dates';
$string['start_date'] = 'Start date';
$string['end_date'] = 'End date';
$string['startdate_inf_enddate'] = 'End period date must be later than start period date';
$string['default_period_name'] = 'Default period';
$string['period_goals'] = 'Period goals';
$string['period_goals_view'] = 'View';
$string['period_goals_edit'] = 'Edit';
$string['nostudent'] = 'No student defined.';
$string['goal'] = 'Goals';
$string['period_appraisals'] = 'Period appraisals';
$string['appraisal'] = 'Appraisal';
$string['emptystudentorperiod'] = 'no student or period id found';
$string['nogoal'] = 'No goal';
$string['noappraisal'] = 'No appraisal';


//Grading
$string['name'] = 'First Name / Surname';
$string['email'] = 'Email Adress';
$string['student_rate'] = 'Student Completion Rate (%)';
$string['student_last'] = 'Last Modified';
$string['teacher_rate'] = 'Teacher Completion rate (%)';
$string['teacher_last'] = 'Last Modified';
$string['feedback'] = 'Feedback';
$string['status'] = 'Status';
$string['action_grade'] = 'Grade';
$string['action_update'] = 'Update';
$string['by'] = 'by';

// Report
$string['input'] = 'Input';
$string['output'] = 'Output';
$string['criteria_category'] = 'Group of outcomes : ';
$string['all_categories'] = 'All groups';
$string['criteria_display'] = 'Display : ';
$string['all_displays'] = 'All displays';
$string['results_per_outcome'] = 'Results for each outcome : ';
$string['no_results'] = 'There are no result corresponding to the selected criteria';
$string['mean_results'] = 'Mean of the results with following criteria :';
$string['mean_calculation_impossible'] = 'Calculation of the mean is impossible : all outcomes have not the same scale.';
$string['for_category'] = 'group';
$string['for_display'] = 'display';
$string['filter_scale'] = 'Scale : ';
$string['filter_category'] = 'Group of outcomes : ';
$string['filter_display'] = 'Display : ';
$string['filter_outcome'] = 'Outcome : ';
$string['teacher_student'] = 'Teacher and Student';
$string['teacher_only'] = 'Teacher only';
$string['student_only'] = 'Student only';
$string['teacher'] = 'Teacher';
$string['student'] = 'Student';
$string['all'] = 'All';
$string['calculate'] = 'Calculate';
$string['student_selection'] = 'Student\'s scale';
$string['teacher_selection'] = 'Teacher\'s scale';
$string['teacherrate'] = 'Teacher (%)';
$string['studentrate'] = 'Student (%)';
$string['counter'] = 'Counter';
$string['not_graded'] = 'No answer';
$string['answer'] = 'Answer';
$string['no_category'] = 'No group found';
$string['no_display'] = 'No display found';
$string['calculation_impossible_for_categories'] = 'Calculation is impossible for the following groups which contains outcomes linked with distinct scales :';
$string['calculation_impossible_for_displays'] = 'Calculation is impossible for the following displays are applied on outcomes linked with distinct scales :';
$string['value'] = 'value';
$string['scale'] = 'scale';
$string['gradeexporttype'] = 'Export type';
$string['gradeexportsource'] = 'Grades to be exported';
$string['no_scale_found'] = 'No scale found';
$string['criteria_period'] = 'Period : ';
$string['all_periods'] = 'All periods';
$string['criteria_student'] = 'Student : ';
$string['all_students'] = 'All students';
$string['criteria_valuetype'] = 'Value\'s type : ';
$string['valuetype_percent'] = 'Percent';
$string['valuetype_color'] = 'Color';
$string['criteria_student_group'] = 'Student\'s group : ';
$string['all_student_groups'] = 'All groups';
$string['sepsemicolon'] = 'Semicolon';
$string['exportcounter'] = 'Include counter in export';

// export PDF
$string['student_comment'] = 'Comment of the student : ';
$string['teacher_feedback'] = 'Feedback of the teacher : ';
$string['attached_documents'] = 'Documents : ';
$string['date_pdf'] = 'Export date : ';
$string['exporter'] = 'Exported by : ';
$string['author'] = 'Author : ';
$string['exportpdftoportfolio'] = 'Export a PDF file to portfolio';
$string['exportcategorytoportfolio'] = 'Export validated outcomes from category to portfolio';
$string['invalidcategoryid'] = 'Invalid category ID';
$string['mustprovidecategoryorpdffile'] = 'You must provide a category or a pdf file';
$string['errorstoringpdffile'] = 'Error occured while storing pdf file';
$string['mustprovideexpectedarguments'] = 'You must provide expected arguments';
$string['savegrades'] = 'Press OK to save modified grades or Cancel to leave without saving';
$string['emptyitemlist'] = 'Sorry, the list of validated items is empty, export is impossible';
$string['periodnotfound'] = 'Sorry the selected period was not found';

// events
$string['eventcheckoutcomecomplete'] = 'CheckOutcome complete';
$string['eventeditpageviewed'] = 'Edit page viewed';
$string['eventreportviewed'] = 'Report viewed';
$string['eventstudentchecksupdated'] = 'Student checks updated';
$string['eventstudentdescriptionupdated'] = 'CheckOutcome Item description updated';
$string['eventteacherchecksupdated'] = 'Teacher checks updated';
