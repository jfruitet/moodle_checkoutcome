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

require_once($CFG->dirroot.'/lib/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/export/grade_export_form.php');

/**
 * Base export class
 */
abstract class checkoutcome_grade_export {

    public $plugin; // plgin name - must be filled in subclasses!

    public $grade_items; // list of all course grade items
    public $selfgrade_items; // for students' self grades 
    public $groupid;     // groupid, 0 means all groups
    public $course;      // course object
    public $columns;     // array of grade_items selected for export

    public $previewrows;     // number of rows in preview
    public $export_letters;  // export letters
	public $export_counter; // export counter
    public $export_feedback; // export feedback
    public $userkey;         // export using private user key

    public $updatedgradesonly; // only export updated grades
    public $gradetype; // grade type (e.g. value, scale) for exports
    public $decimalpoints; // number of decimal points for exports
    public $gradesource; // grades to be exported (0 = teacher, 1 = student)
    public $itemmodule;
    public $iteminstance;
    public $categories;
    public $displays;
    public $checkoutcomeid;
    public $selected_period;
	public $periods;
	public $periodide;
   
    /**
     * Constructor should set up all the private variables ready to be pulled
     * @access public
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     * @param string $itemlist comma separated list of item ids, empty means all
     * @param boolean $export_feedback
     * @param boolean $export_letters
     * @note Exporting as letters will lead to data loss if that exported set it re-imported.
     */
    public function checkoutcome_grade_export($course, $groupid=0, $itemlist='', $export_counter=false, $export_feedback=false, $updatedgradesonly = false, $gradetype = GRADE_TYPE_VALUE, $decimalpoints = 2, $gradesource = 0, $itemmodule, $iteminstance, $checkoutcomeid, $periodid = 0) {
        global $DB;
    	$this->course = $course;
        $this->groupid = $groupid;
        $this->checkoutcomeid = $checkoutcomeid;
        $this->periodide = $periodid;
		$this->periods=array();
        // Get selected period
        //if ($periodid) {
        	//$this->selected_period = $DB->get_record('checkoutcome_periods', array('id' => $periodid));
        //}
		if ($periodid==0)
		{
			$this->periods = $DB->get_records('checkoutcome_periods',array('checkoutcome' => $checkoutcomeid),'shortname');
		}
		else
		{
			$this->selected_period = $DB->get_record('checkoutcome_periods', array('id' => $periodid));
		}
        
        // get categories and displays
        $this->categories = $DB->get_records('checkoutcome_category',array('checkoutcome' => $checkoutcomeid));
        $this->displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $checkoutcomeid));
        
        //$this->grade_items = grade_item::fetch_all(array('courseid'=>$this->course->id));
        
        // Getting the list of outcomes for this course
        $outcomes = $DB->get_records('grade_outcomes',array('courseid' => $course->id),'shortname');
        
        // Getting the corresponding grade items
        $this->grade_items = array();
        $this->selfgrade_items = array();
		
		foreach ($outcomes as $outcome) {        	
			$gitem_base = $DB->get_record('grade_items',array('courseid' => $course->id,'outcomeid' => $outcome->id,'itemmodule' => $itemmodule, 'iteminstance' => $iteminstance));
			if ($gitem_base) {        		
					
				$gitem = new grade_item($gitem_base);
				// force grade type to the value given by user
				$gitem->gradetype = $gradetype;
				$this->grade_items[$gitem->id] = $gitem;
					
				// Get corresponding selfgrade item
				$selfg_item = $DB->get_record('checkoutcome_item', array('gradeitem' => $gitem->id));
				// Fill in selgrade items table
				if ($selfg_item) {
					$this->selfgrade_items[$gitem->id]= $this->convertToGradeItem($selfg_item, $gitem);
				}       		
			}
		}

        //Populating the columns here is required by /grade/export/(whatever)/export.php
        //however index.php, when the form is submitted, will construct the collection here
        //with an empty $itemlist then reconstruct it in process_form() using $formdata
        $this->columns = array();
        if (!empty($itemlist)) {
            if ($itemlist=='-1') {
                //user deselected all items
            } else {
                $itemids = explode(',', $itemlist);
                // remove items that are not requested
                foreach ($itemids as $itemid) {                	
                		if (array_key_exists($itemid, $this->selfgrade_items)) {
                			$this->columns[$itemid] =& $this->selfgrade_items[$itemid];
               			}
                }
            }
        } else {        	
        		foreach ($this->selfgrade_items as $itemid=>$unused) {
        			$this->columns[$itemid] =& $this->selfgrade_items[$itemid];
        		}            
        }

		$this->export_counter = $export_counter;
        $this->export_feedback = $export_feedback;
        $this->userkey         = '';
        $this->previewrows     = false;
        $this->updatedgradesonly = $updatedgradesonly;

        $this->gradetype = $gradetype;
        $this->gradesource = $gradesource;
        $this->decimalpoints = $decimalpoints;
    }
    
    public function convertToGradeItem($sitem, $gitem) {
    	
    	$new_gitem = new grade_item($gitem);
    	
    	$new_gitem->gradeitem = $sitem->gradeitem;
    	$new_gitem->category = $sitem->category;
    	$new_gitem->display = $sitem->display;
    	$new_gitem->timecreated = $sitem->timecreated;
    	$new_gitem->timemodified = $sitem->timemodified;
    	
    	// introduce new parameter needed to export selfgrades
    	$new_gitem->checkoutcomeitem = $sitem->id;
    	
    	return $new_gitem;
    }
    	

    /**
     * Init object based using data from form
     * @param object $formdata
     */
    function process_form($formdata) {
        global $USER;

        $this->columns = array();
        if (!empty($formdata->itemids)) {
            if ($formdata->itemids=='-1') {
                //user deselected all items
            } else {
                foreach ($formdata->itemids as $itemid=>$selected) {                	
                		if ($selected and array_key_exists($itemid, $this->selfgrade_items)) {
                			$this->columns[$itemid] =& $this->selfgrade_items[$itemid];
                		}                    
                }
            }
        } else {            
            	foreach ($this->selfgrade_items as $itemid=>$unused) {
            		$this->columns[$itemid] =& $this->selfgrade_items[$itemid];
            	}                    	
        }

        if (isset($formdata->key)) {
            if ($formdata->key == 1 && isset($formdata->iprestriction) && isset($formdata->validuntil)) {
                // Create a new key
                $formdata->key = create_user_key('grade/export', $USER->id, $this->course->id, $formdata->iprestriction, $formdata->validuntil);
            }
            $this->userkey = $formdata->key;
        }

        if (isset($formdata->export_letters)) {
            $this->export_letters = $formdata->export_letters;
        }

		if (isset($formdata->export_counter)) {
            $this->export_counter = $formdata->export_counter;
        }
		
        if (isset($formdata->export_feedback)) {
            $this->export_feedback = $formdata->export_feedback;
        }

        if (isset($formdata->previewrows)) {
            $this->previewrows = $formdata->previewrows;
        }

    }

    /**
     * Update exported field in grade_grades table
     * @return boolean
     */
    public function track_exports() {
        global $CFG;

        /// Whether this plugin is entitled to update export time
        if ($expplugins = explode(",", $CFG->gradeexport)) {
            if (in_array($this->plugin, $expplugins)) {
                return true;
            } else {
                return false;
          }
        } else {
            return false;
        }
    }

    /**
     * Returns string representation of final grade
     * @param $object $grade instance of grade_grade class
     * @return string
     */
    public function format_grade($grade) {
        return grade_format_gradevalue($grade->grade, $this->grade_items[$grade->itemid], false, 1, 0);
    }

    /**
     * Returns the name of column in export
     * @param object $grade_item
     * @param boolena $feedback feedback colum
     * &return string
     */
    public function format_column_name($grade_item, $counter=false, $feedback=false, $source=null) {
        if ($grade_item->itemtype == 'mod') {
            $name = /*get_string('modulename', $grade_item->itemmodule).get_string('labelsep', 'langconfig').*/$grade_item->itemname;
        } else {
            $name = $grade_item->itemname;
        }
        
        if ($source != null) {
        	$name .= ' - ';
        	switch ($source) {
    			case 'teacher' :
    				$name .= get_string('teacher','checkoutcome');
    				break;
    			case 'student' :
    				$name .= get_string('student','checkoutcome');
    				break;
        	}
        }

		if ($counter) {
            $name .= ' ('.get_string('counter','checkoutcome').')';
        }
		
        if ($feedback) {
            $name .= ' ('.get_string('feedback').')';
        }
        
       

        return strip_tags($name);
    }

    /**
     * Returns formatted grade feedback
     * @param object $feedback object with properties feedback and feedbackformat
     * @return string
     */
    public function format_feedback($feedback) {
        return strip_tags(format_text($feedback->feedback, $feedback->feedbackformat));
    }

    /**
     * Implemented by child class
     */
    public abstract function print_grades();

    /**
     * Prints preview of exported grades on screen as a feedback mechanism
     * @param bool $require_user_idnumber true means skip users without idnumber
     */
    public function display_preview($require_user_idnumber=false) {
        global $DB, $OUTPUT, $USER, $COURSE, $CFG;
        echo $OUTPUT->heading(get_string('previewrows', 'grades'));

		if ($CFG->version < 2011120100) {
		    $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		}
		else {
			$context = context_course::instance($COURSE->id);
		}

		if($this->periodide==0)
		{
			echo '<table class="export_preview">';
			// Print outcome title
			echo '<tr>';
			echo '<th>'.get_string('outcome','checkoutcome').'</th>'.
				 '<th></th>'.
				 '<th></th>';
			foreach ($this->periods as $per) {
				foreach ($this->columns as $grade_item) {
					if ($this->gradesource != 2) {
						echo '<th>'.$this->format_column_name($grade_item).'</th>';
						
						/// add a column_feedback column
						if ($this->export_feedback) {
							echo '<th>'.$this->format_column_name($grade_item, false, true).'</th>';
						}
						
						/// add a column_counter column
						if ($this->export_counter) {
							echo '<th>'.$this->format_column_name($grade_item, true).'</th>';
						}
					} else {
						//teacher
						echo '<th>'.$this->format_column_name($grade_item, false, false, 'teacher').'</th>';
						
						/// add a column_feedback column
						if ($this->export_feedback) {
							echo '<th>'.$this->format_column_name($grade_item, false, true, 'teacher').'</th>';
						}
						//student
						echo '<th>'.$this->format_column_name($grade_item, false, false, 'student').'</th>';
						 
						/// add a column_feedback column
						if ($this->export_feedback) {
							echo '<th>'.$this->format_column_name($grade_item, false, true, 'student').'</th>';
						}
						
						/// add a column_counter column
						if ($this->export_counter) {
							echo '<th>'.$this->format_column_name($grade_item, true).'</th>';
						}
					}
				}
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print category
			echo '<tr>';
			echo '<th>'.get_string('category','checkoutcome').'</th>'.
				'<th></th>'.  
				'<th></th>';
			foreach ($this->periods as $per) {
				foreach ($this->columns as $item) {
					if ($this->selfgrade_items[$item->id]->category != 0) {
						echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
						if ($this->gradesource == 2) {
							echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
						}
					} else {
						echo '<th>-</th>';
						if ($this->gradesource == 2) {
							echo '<th>-</th>';
						}
					}      	
						
					/// add a column_feedback column        	
					if ($this->export_feedback) {
						if ($this->selfgrade_items[$item->id]->category != 0) {
							echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
							if ($this->gradesource == 2) {
								echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
							}
						} else {
							echo '<th>-</th>';
							if ($this->gradesource == 2) {
										echo '<th>-</th>';
							}
						}	        	
					}  

					/// add a column_counter column        	
					if ($this->export_counter) {
						if ($this->selfgrade_items[$item->id]->category != 0) {
							echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
						} else {
							echo '<th>-</th>';
						}	        	
					}     
				}
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print display
			echo '<tr>';
			echo '<th>'.get_string('display','checkoutcome').'</th>'.
					'<th></th>'.
					'<th></th>';
			foreach ($this->periods as $per) {
				foreach ($this->columns as $item) {
					if (!empty($this->selfgrade_items[$item->id]->display)) {
						echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
						if ($this->gradesource == 2) {
							echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
						}
					} else {
						echo '<th>-</th>';
						if ($this->gradesource == 2) {
							echo '<th>-</th>';
						}
					}
					/// add a column_feedback column        	
					if ($this->export_feedback) {
						if (!empty($this->selfgrade_items[$item->id]->display)) {
							echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
							if ($this->gradesource == 2) {
								echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
							}
						} else {
							echo '<th>-</th>';
							if ($this->gradesource == 2) {
								echo '<th>-</th>';
							}
						}	       		
					}

					/// add a column_counter column        	
					if ($this->export_counter) {
						if (!empty($this->selfgrade_items[$item->id]->display)) {
							echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
						} else {
							echo '<th>-</th>';
						}	       		
					}  
				}
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print period
			echo '<tr>';
			echo '<th>'.get_string('period','checkoutcome').'</th>'.
					'<th></th>'.
					'<th></th>';
			foreach ($this->periods as $per) {
				foreach ($this->columns as $sg_item) {
					echo '<th>'.$per->name.'</th>';
					if ($this->gradesource == 2) {
						echo '<th>'.$per->name.'</th>';
					}
					/// add a column_feedback column
					if ($this->export_feedback) {
						echo '<th>'.$per->name.'</th>';
						if ($this->gradesource == 2) {
							echo '<th>'.$per->name.'</th>';
						}
					}
					/// add a column_counter column
					if ($this->export_counter) {
						echo '<th>'.$per->name.'</th>';
					}
				}
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print firstname, lastname, email
			echo '<tr>';
			echo '<th>'.get_string("firstname")."</th>".
					'<th>'.get_string("lastname")."</th>".
					'<th>'.get_string("email")."</th>";
			foreach ($this->periods as $per) {
				foreach ($this->columns as $grade_item) {
					echo '<th></th>';
					if ($this->gradesource == 2) {
						echo '<th></th>';
					}
					/// add a column_feedback column
					if ($this->export_feedback) {
						echo '<th></th>';
						if ($this->gradesource == 2) {
							echo '<th></th>';
						}
					}    
					
					/// add a column_counter column
					if ($this->export_counter) {
						echo '<th></th>';
					}  
				}
			}
			echo '</tr>';
			/// Print all the lines of data.
			$i = 0;
			$gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
			$gui->init();
			while ($userdata = $gui->next_user()) {
				// number of preview rows
				if ($this->previewrows and $this->previewrows <= $i) {
					break;
				}
				$user = $userdata->user;
				
				$query1bis = 'select groupid from {groups_members} where userid='. $USER->id .';';
				$groups = $DB->get_records_sql( $query1bis );
				
				///////////////
				$query1 = 'select distinct(r.archetype) from {role} as r, {role_assignments} as ra where r.id=ra.roleid and ra.contextid='.$context->id.' and ra.userid=' . $USER->id . ';';
				$roles = $DB->get_records_sql( $query1 );
				$roleid='';
				foreach ($roles as $role) {
					$roleid=$role->archetype;
				}
				if($roleid=='student')
				{
					$query2= 'select u.id as userid from {user} as u where u.id='.$USER->id.'';
					$usersgroupsids = $DB->get_records_sql( $query2 );
				}
				else if($roleid=='teacher')
				{
					if(!$groups)
					{
						$query2= 'select ra.userid from {role_assignments} as ra, {role} as r where ra.contextid=' . $context->id . ' and r.archetype="student" and ra.roleid=r.id';
						$usersgroupsids = $DB->get_records_sql( $query2 );
					}
					else
					{
						$query2= 'select g.userid from {groups_members} as g, {role_assignments} as ra, {role} as r where ra.contextid=' . $context->id . ' and g.userid=ra.userid and r.archetype="student" and ra.roleid=r.id and g.groupid IN (select groupid from {groups_members} where userid='. $USER->id .');';
						$usersgroupsids = $DB->get_records_sql( $query2 );
					}
				}
				else
				{
					$query3= 'select ra.userid from {role_assignments} as ra, {role} as r where ra.contextid=' . $context->id . ' and r.archetype="student" and ra.roleid=r.id;';
					$usersgroupsids = $DB->get_records_sql( $query3 );
				}
			
				foreach ($usersgroupsids as $usergroup) {
					if($usergroup->userid==$user->id)
					{
				/////////////
						if ($require_user_idnumber and empty($user->idnumber)) {
							// some exports require user idnumber so we can match up students when importing the data
							continue;
						}

						$gradeupdated = false; // if no grade is update at all for this user, do not display this row
						$rowstr = '';
						foreach ($this->periods as $per) {
							foreach ($this->columns as $item) {
								if ($this->gradesource == 0 || $this->gradesource == 2) {
									$gr = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $per->id));
									if ($gr != null && $gr->grade != null && $gr->grade != -1) {
										if ($this->gradetype == GRADE_TYPE_VALUE) {
											$gradetxt = $gr->grade;
										} else {
											// get scale first
											$scale = $DB->get_record('scale', array('id' => $item->scaleid));
											// get scale items
											if ($scale != null) {
												$scaleitems = explode(',',$scale->scale);
												$gradetxt = $scaleitems[$gr->grade - 1];
											} else {
												$gradetxt = get_string('no_scale_found',checkoutcome);
											}
										}
									} else {
										$gradetxt = '-';
									}
									
									$rowstr .= "<td align='center'>$gradetxt</td>";
									 
									if ($this->export_feedback) {
										if ($gr != null && $gr->comment != null) {
											$rowstr .=  '<td align=\'center\'>'.$gr->comment.'</td>';
										} else {
											$rowstr .= '<td align=\'center\'>-</td>';
										}
									}				
								} 
								if ($this->gradesource == 1 || $this->gradesource == 2) {            	          		
									$gr = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $per->id));
									if ($gr != null && $gr->grade != null && $gr->grade != -1) {
										if ($this->gradetype == GRADE_TYPE_VALUE) {
											$gradetxt = $gr->grade;
										} else {
											// get scale first
											$scale = $DB->get_record('scale', array('id' => $item->scaleid));
											// get scale items
											if ($scale != null) {
												$scaleitems = explode(',',$scale->scale);
												$gradetxt = $scaleitems[$gr->grade - 1];
											} else {
												$gradetxt = get_string('no_scale_found',checkoutcome);
											}
										}            			
									} else { 
										$gradetxt = '-';
									}
									
									$rowstr .= "<td align='center'>$gradetxt</td>";
								
									if ($this->export_feedback) {
										if ($gr != null && $gr->comment != null) {
											$rowstr .=  '<td align=\'center\'>'.$gr->comment.'</td>';
										} else {
											$rowstr .= '<td align=\'center\'>-</td>';
										}           			
									}            	
								}
								
								if ($this->export_counter) {
									$gr = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $per->id));
									if ($gr != null && $gr->count != null) {
										$rowstr .=  '<td align=\'center\'>'.$gr->count.'</td>';
									} else {
										$rowstr .= '<td align=\'center\'>-</td>';
									}           			
								}
							}
						}
					
						echo '<tr>';
						echo "<td>$user->firstname</td><td>$user->lastname</td><td>$user->email</td>";
						echo $rowstr;
						echo "</tr>";
						$i++; // increment the counter
					}
				}
			}
			echo '</table>';
			$gui->close();
		}
		else
		{
			echo '<table class="export_preview">';
			// Print outcome title
			echo '<tr>';
			echo '<th>'.get_string('outcome','checkoutcome').'</th>'.
				 '<th></th>'.
				 '<th></th>';
			foreach ($this->columns as $grade_item) {
				if ($this->gradesource != 2) {
					echo '<th>'.$this->format_column_name($grade_item).'</th>';
			
					/// add a column_feedback column
					if ($this->export_feedback) {
						echo '<th>'.$this->format_column_name($grade_item, false, true).'</th>';
					}
					
					/// add a column_counter column
					if ($this->export_counter) {
						echo '<th>'.$this->format_column_name($grade_item, true).'</th>';
					}
				} else {
					//teacher
					echo '<th>'.$this->format_column_name($grade_item, false, false, 'teacher').'</th>';
						
					/// add a column_feedback column
					if ($this->export_feedback) {
						echo '<th>'.$this->format_column_name($grade_item, false, true, 'teacher').'</th>';
					}
					//student
					echo '<th>'.$this->format_column_name($grade_item, false, false, 'student').'</th>';
						 
					/// add a column_feedback column
					if ($this->export_feedback) {
						echo '<th>'.$this->format_column_name($grade_item, false, true, 'student').'</th>';
					}
					
					/// add a column_counter column
					if ($this->export_counter) {
						echo '<th>'.$this->format_column_name($grade_item, true).'</th>';
					}
				}
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print category
			echo '<tr>';
			echo '<th>'.get_string('category','checkoutcome').'</th>'.
				'<th></th>'.  
				'<th></th>';
			foreach ($this->columns as $item) {       	
				if ($this->selfgrade_items[$item->id]->category != 0) {
					echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
					if ($this->gradesource == 2) {
						echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
					}
				} else {
					echo '<th>-</th>';
					if ($this->gradesource == 2) {
						echo '<th>-</th>';
					}
				}      	
					
				/// add a column_feedback column        	
				if ($this->export_feedback) {
					if ($this->selfgrade_items[$item->id]->category != 0) {
						echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
						if ($this->gradesource == 2) {
							echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
						}
					} else {
						echo '<th>-</th>';
						if ($this->gradesource == 2) {
									echo '<th>-</th>';
						}
					}	        	
				}    
				
				/// add a column_counter column
				if ($this->export_counter) {
					if ($this->selfgrade_items[$item->id]->category != 0) {
						echo '<th>'.$this->categories[$this->selfgrade_items[$item->id]->category]->name.'</th>';
					} else {
						echo '<th>-</th>';
					}	        	
				} 
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print display
			echo '<tr>';
			echo '<th>'.get_string('display','checkoutcome').'</th>'.
					'<th></th>'.
					'<th></th>';
			foreach ($this->columns as $item) {
				if (!empty($this->selfgrade_items[$item->id]->display)) {
					echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
					if ($this->gradesource == 2) {
						echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
					}
				} else {
					echo '<th>-</th>';
					if ($this->gradesource == 2) {
						echo '<th>-</th>';
					}
				}
				/// add a column_feedback column        	
				if ($this->export_feedback) {
					if (!empty($this->selfgrade_items[$item->id]->display)) {
						echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
						if ($this->gradesource == 2) {
							echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
						}
					} else {
						echo '<th>-</th>';
						if ($this->gradesource == 2) {
							echo '<th>-</th>';
						}
					}	       		
				}  

				/// add a column_counter column        	
				if ($this->export_counter) {
					if (!empty($this->selfgrade_items[$item->id]->display)) {
						echo '<th>'.$this->displays[$this->selfgrade_items[$item->id]->display]->name.'</th>';
					} else {
						echo '<th>-</th>';
					}	       		
				}
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print period
			echo '<tr>';
			echo '<th>'.get_string('period','checkoutcome').'</th>'.
					'<th></th>'.
					'<th></th>';
			foreach ($this->columns as $sg_item) {
				echo '<th>'.$this->selected_period->name.'</th>';
				if ($this->gradesource == 2) {
					echo '<th>'.$this->selected_period->name.'</th>';
				}
				/// add a column_feedback column
				if ($this->export_feedback) {
					echo '<th>'.$this->selected_period->name.'</th>';
					if ($this->gradesource == 2) {
						echo '<th>'.$this->selected_period->name.'</th>';
					}
				}
				
				/// add a column_counter column
				if ($this->export_counter) {
					echo '<th>'.$this->selected_period->name.'</th>';
				}
			}
			echo '</tr><tr><th>---<th></tr>';
			//Print firstname, lastname, email
			echo '<tr>';
			echo '<th>'.get_string("firstname")."</th>".
					'<th>'.get_string("lastname")."</th>".
					'<th>'.get_string("email")."</th>";
			foreach ($this->columns as $grade_item) {
				echo '<th></th>';
				if ($this->gradesource == 2) {
					echo '<th></th>';
				}
				/// add a column_feedback column
				if ($this->export_feedback) {
					echo '<th></th>';
					if ($this->gradesource == 2) {
						echo '<th></th>';
					}
				} 

				/// add a column_counter column
				if ($this->export_counter) {
					echo '<th></th>';
				} 
			}
			echo '</tr>';
			/// Print all the lines of data.
			$i = 0;
			$gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
			$gui->init();
			while ($userdata = $gui->next_user()) {
				// number of preview rows
				if ($this->previewrows and $this->previewrows <= $i) {
					break;
				}
				$user = $userdata->user;
				
				$query1bis = 'select groupid from {groups_members} where userid='. $USER->id .';';
				$groups = $DB->get_records_sql( $query1bis );
				
				///////////////
				$query1 = 'select distinct(r.archetype) from {role} as r, {role_assignments} as ra where r.id=ra.roleid and ra.contextid='.$context->id.' and ra.userid=' . $USER->id . ';';
				$roles = $DB->get_records_sql( $query1 );
				$roleid='';
				foreach ($roles as $role) {
					$roleid=$role->archetype;
				}
				if($roleid=='student')
				{
					$query2= 'select u.id as userid from {user} as u where u.id='.$USER->id.'';
					$usersgroupsids = $DB->get_records_sql( $query2 );
				}
				else if($roleid=='teacher')
				{
					if(!$groups)
					{
						$query2= 'select ra.userid from {role_assignments} as ra, {role} as r where ra.contextid=' . $context->id . ' and r.archetype="student" and ra.roleid=r.id';
						$usersgroupsids = $DB->get_records_sql( $query2 );
					}
					else
					{
						$query2= 'select g.userid from {groups_members} as g, {role_assignments} as ra, {role} as r where ra.contextid=' . $context->id . ' and g.userid=ra.userid and r.archetype="student" and ra.roleid=r.id and g.groupid IN (select groupid from {groups_members} where userid='. $USER->id .');';
						$usersgroupsids = $DB->get_records_sql( $query2 );
					}
				}
				else
				{
					$query3= 'select ra.userid from {role_assignments} as ra, {role} as r where ra.contextid=' . $context->id . ' and r.archetype="student" and ra.roleid=r.id;';
					$usersgroupsids = $DB->get_records_sql( $query3 );
				}
			
				foreach ($usersgroupsids as $usergroup) {
					if($usergroup->userid==$user->id)
					{
				/////////////
				
						if ($require_user_idnumber and empty($user->idnumber)) {
							// some exports require user idnumber so we can match up students when importing the data
							continue;
						}

						$gradeupdated = false; // if no grade is update at all for this user, do not display this row
						$rowstr = '';
						foreach ($this->columns as $item) {
							if ($this->gradesource == 0 || $this->gradesource == 2) {
													  
			// 		            	$gradetxt = $this->format_grade($userdata->grades[$item->id]);
					
			// 		                // get the status of this grade, and put it through track to get the status
			// 		                $g = new grade_export_update_buffer();
			// 		                $grade_grade = new grade_grade(array('itemid'=>$item->id, 'userid'=>$user->id));
			// 		                $status = $g->track($grade_grade);
					
			// 		                if ($this->updatedgradesonly && ($status == 'nochange' || $status == 'unknown')) {
			// 		                    $rowstr .= '<td>'.get_string('unchangedgrade', 'grades').'</td>';
			// 		                } else {
			// 		                    $rowstr .= "<td>$gradetxt</td>";
			// 		                    $gradeupdated = true;
			// 		                }
					
			// 		                if ($this->export_feedback) {
			// 		                    $rowstr .=  '<td>'.$this->format_feedback($userdata->feedbacks[$item->id]).'</td>';
			// 		                }
								$gr = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $this->selected_period->id));
								if ($gr != null && $gr->grade != null && $gr->grade != -1) {
									if ($this->gradetype == GRADE_TYPE_VALUE) {
										$gradetxt = $gr->grade;
									} else {
										// get scale first
										$scale = $DB->get_record('scale', array('id' => $item->scaleid));
										// get scale items
										if ($scale != null) {
											$scaleitems = explode(',',$scale->scale);
											$gradetxt = $scaleitems[$gr->grade - 1];
										} else {
											$gradetxt = get_string('no_scale_found',checkoutcome);
										}
									}
								} else {
									$gradetxt = '-';
								}
								
								$rowstr .= "<td align='center'>$gradetxt</td>";
								 
								if ($this->export_feedback) {
									if ($gr != null && $gr->comment != null) {
										$rowstr .=  '<td align=\'center\'>'.$gr->comment.'</td>';
									} else {
										$rowstr .= '<td align=\'center\'>-</td>';
									}
								}				
							} 
							if ($this->gradesource == 1 || $this->gradesource == 2) {            	          		
								$gr = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $this->selected_period->id));
								if ($gr != null && $gr->grade != null && $gr->grade != -1) {
									if ($this->gradetype == GRADE_TYPE_VALUE) {
										$gradetxt = $gr->grade;
									} else {
										// get scale first
										$scale = $DB->get_record('scale', array('id' => $item->scaleid));
										// get scale items
										if ($scale != null) {
											$scaleitems = explode(',',$scale->scale);
											$gradetxt = $scaleitems[$gr->grade - 1];
										} else {
											$gradetxt = get_string('no_scale_found',checkoutcome);
										}
									}            			
								} else { 
									$gradetxt = '-';
								}
								
								$rowstr .= "<td align='center'>$gradetxt</td>";
							
								if ($this->export_feedback) {
									if ($gr != null && $gr->comment != null) {
										$rowstr .=  '<td align=\'center\'>'.$gr->comment.'</td>';
									} else {
										$rowstr .= '<td align=\'center\'>-</td>';
									}           			
								}            	
							}
							
							if ($this->export_counter) {
								$gr = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $this->selected_period->id));
								if ($gr != null && $gr->count != null) {
									$rowstr .=  '<td align=\'center\'>'.$gr->count.'</td>';
								} else {
									$rowstr .= '<td align=\'center\'>-</td>';
								}           			
							} 
						}
						// if we are requesting updated grades only, we are not interested in this user at all
			//             if (!$gradeupdated && $this->updatedgradesonly) {
			//                 continue;
			//             }

						echo '<tr>';
						//echo "<td>$user->firstname</td><td>$user->lastname</td><td>$user->idnumber</td><td>$user->institution</td><td>$user->department</td><td>$user->email</td>";
						echo "<td>$user->firstname</td><td>$user->lastname</td><td>$user->email</td>";
						echo $rowstr;
						echo "</tr>";

						$i++; // increment the counter
					}
				}
			}
			echo '</table>';
			$gui->close();
		}

        
    }

    /**
     * Returns array of parameters used by dump.php and export.php.
     * @return array
     */
    public function get_export_params() {
        $itemids = array_keys($this->columns);
        $itemidsparam = implode(',', $itemids);
        if (empty($itemidsparam)) {
            $itemidsparam = '-1';
        }

        $params = array('id'                =>$this->course->id,
                        'groupid'           =>$this->groupid,
                        'itemids'           =>$itemidsparam,
                        'export_letters'    =>$this->export_letters,
						'export_counter'    =>$this->export_counter,
                        'export_feedback'   =>$this->export_feedback,
                        'updatedgradesonly' =>$this->updatedgradesonly,
                        'gradetype'       	=>$this->gradetype,
                        'decimalpoints'     =>$this->decimalpoints,
        				'gradesource' 		=>$this->gradesource,
        				'itemmodule'		=>$this->itemmodule,
        				'iteminstance' 		=>$this->iteminstance,
        				'checkoutcomeid' 	=>$this->checkoutcomeid,
						'periodide'	=>$this->periodide);

        return $params;
    }

    /**
     * Either prints a "Export" box, which will redirect the user to the download page,
     * or prints the URL for the published data.
     * @return void
     */
    public function print_continue() {
        global $CFG, $OUTPUT;

        $params = $this->get_export_params();

        echo $OUTPUT->heading(get_string('export', 'grades'));

        echo $OUTPUT->container_start('gradeexportlink');

        if (!$this->userkey) {      // this button should trigger a download prompt
            echo $OUTPUT->single_button(new moodle_url('/mod/checkoutcome/export/'.$this->plugin.'/export.php', $params), get_string('download', 'admin'));
            echo '<br>';
            echo $OUTPUT->single_button(new moodle_url('/mod/checkoutcome/export.php', array('checkoutcome' => $this->checkoutcomeid)), get_string('back', 'checkoutcome'));            
        } else {
            $paramstr = '';
            $sep = '?';
            foreach($params as $name=>$value) {
                $paramstr .= $sep.$name.'='.$value;
                $sep = '&';
            }

            $link = $CFG->wwwroot.'/grade/export/'.$this->plugin.'/dump.php'.$paramstr.'&key='.$this->userkey;

            echo get_string('download', 'admin').': ' . html_writer::link($link, $link);
        }
        echo $OUTPUT->container_end();
    }
}

/**
 * This class is used to update the exported field in grade_grades.
 * It does internal buffering to speedup the db operations.
 */
class checkoutcome_grade_export_update_buffer {
    public $update_list;
    public $export_time;

    /**
     * Constructor - creates the buffer and initialises the time stamp
     */
    public function checkoutcome_grade_export_update_buffer() {
        $this->update_list = array();
        $this->export_time = time();
    }

    public function flush($buffersize) {
        global $CFG, $DB;

        if (count($this->update_list) > $buffersize) {
            list($usql, $params) = $DB->get_in_or_equal($this->update_list);
            $params = array_merge(array($this->export_time), $params);

            $sql = "UPDATE {grade_grades} SET exported = ? WHERE id $usql";
            $DB->execute($sql, $params);
            $this->update_list = array();
        }
    }

    /**
     * Track grade export status
     * @param object $grade_grade
     * @return string $status (unknow, new, regrade, nochange)
     */
    public function track($grade_grade) {

        if (empty($grade_grade->exported) or empty($grade_grade->timemodified)) {
            if (is_null($grade_grade->finalgrade)) {
                // grade does not exist yet
                $status = 'unknown';
            } else {
                $status = 'new';
                $this->update_list[] = $grade_grade->id;
            }

        } else if ($grade_grade->exported < $grade_grade->timemodified) {
            $status = 'regrade';
            $this->update_list[] = $grade_grade->id;

        } else if ($grade_grade->exported >= $grade_grade->timemodified) {
            $status = 'nochange';

        } else {
            // something is wrong?
            $status = 'unknown';
        }

        $this->flush(100);

        return $status;
    }

    /**
     * Flush and close the buffer.
     */
    public function close() {
        $this->flush(0);
    }
}

