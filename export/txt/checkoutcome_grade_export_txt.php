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

require_once($CFG->dirroot.'/mod/checkoutcome/export/lib.php');

class checkoutcome_grade_export_txt extends checkoutcome_grade_export {

    public $plugin = 'txt';

    public $separator; // default separator
    
    public function __construct($course, $groupid=0, $itemlist='', $export_counter=false, $export_feedback=false, $updatedgradesonly = false, $gradetype = GRADE_TYPE_VALUE, $decimalpoints = 2, $gradesource = 0, $separator='comma', $itemmodule, $iteminstance, $checkoutcomeid, $periodid = 0) {
        parent::__construct($course, $groupid, $itemlist, $export_counter, $export_feedback, $updatedgradesonly, $gradetype, $decimalpoints, $gradesource, $itemmodule, $iteminstance, $checkoutcomeid, $periodid);
        $this->separator = $separator;
        $this->itemmodule = $itemmodule;
        $this->iteminstance = $iteminstance; 
		//$this->periodid = $periodid;		
    }

    public function get_export_params() {
        $params = parent::get_export_params();
        $params['separator'] = $this->separator;
        return $params;
    }

    public function print_grades() {
        global $CFG,$DB,$USER,$COURSE;
		
        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');

        switch ($this->separator) {
            case 'comma':
                $separator = ",";
                break;
			case 'semicolon':
                $separator = ";";
                break;
            case 'tab':
            default:
                $separator = "\t";
        }
		
        /// Print header to force download
        if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
            @header('Cache-Control: max-age=10');
            @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            @header('Pragma: ');
        } else { //normal http - prevent caching at all cost
            @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            @header('Pragma: no-cache');
        }
        header("Content-Type: application/download\n");
        if ($CFG->version < 2011120100) {
		    $contextthis = get_context_instance(CONTEXT_COURSE, $this->course->id);
		}
		else {
			$contextthis = context_course::instance($this->course->id);
		}
        $shortname = format_string($this->course->shortname, true, array('context' => $contextthis));
        $downloadfilename = clean_filename("$shortname $strgrades");
        header("Content-Disposition: attachment; filename=\"$downloadfilename.txt\"");
		if ($CFG->version < 2011120100) {
			$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		}
		else {
			$context = context_course::instance($COURSE->id);
		}

        if($this->periodide==0)
		{
			/// Print names of the periods
			echo get_string('period','checkoutcome').$separator.
			$separator;
			
			foreach ($this->periods as $per) {
				foreach ($this->columns as $grade_item) 
				{
					if ($this->gradesource != 2) {
						echo $separator.$per->name;
					
						// add a feedback column
						if ($this->export_feedback) {
							echo $separator.$per->name;
						}
					} else {
						//teacher
						echo $separator.$per->name;  		
						if ($this->export_feedback) {
							echo $separator.$per->name;
						}
						//student
						echo $separator.$per->name;
						if ($this->export_feedback) {
							echo $separator.$per->name;
						}
					}
					// add a counter column
					if ($this->export_counter) {
						echo $separator.$per->name;
					}
				}
			}
			
			echo "\n";
		
			/// Print names of the outcomes
			echo get_string('outcome','checkoutcome').$separator.
			$separator;
		   
			foreach ($this->periods as $per) {
				foreach ($this->columns as $grade_item) {
					if ($this->gradesource != 2) {
						echo $separator.$this->format_column_name($grade_item);
					
						// add a feedback column
						if ($this->export_feedback) {
							echo $separator.$this->format_column_name($grade_item, false, true);
						}
					} else {
						//teacher
						echo $separator.$this->format_column_name($grade_item,false,false,'teacher');        		
						if ($this->export_feedback) {
							echo $separator.$this->format_column_name($grade_item,false,true,'teacher');
						}
						//student
						echo $separator.$this->format_column_name($grade_item,false,false,'student');
						if ($this->export_feedback) {
							echo $separator.$this->format_column_name($grade_item,false,true,'student');
						}
					}
					// add a feedback column
					if ($this->export_counter) {
						echo $separator.$this->format_column_name($grade_item, true);
					}
				}
			}
			echo "\n";
			
			/// Print category
			echo get_string('category','checkoutcome').$separator.
			$separator;
			
			foreach ($this->periods as $per) {
				foreach ($this->columns as $item) {
					if ($this->selfgrade_items[$item->id]->category != 0) {
						echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
						if ($this->gradesource == 2) {
							echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
						}
					} else {
						echo $separator;
						if ($this->gradesource == 2) {
							echo $separator;
						}
					}      	
						
					/// add a column_feedback column        	
					if ($this->export_feedback) {
						if ($this->selfgrade_items[$item->id]->category != 0) {
							echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
							if ($this->gradesource == 2) {
								echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
							}
						} else {
							echo $separator;
							if ($this->gradesource == 2) {
								echo $separator;
							}
						}	        	
					}
					
					/// add a column_counter column        	
					if ($this->export_counter) {
						if ($this->selfgrade_items[$item->id]->category != 0) {
							echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
						} else {
							echo $separator;
						}	        	
					} 
				}
			}
			echo "\n";

			/// Print display
			echo get_string('display','checkoutcome').$separator.
			$separator;
			
			foreach ($this->periods as $per) {
				foreach ($this->columns as $item) {
					if (!empty($this->selfgrade_items[$item->id]->display)) {
					echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
						if ($this->gradesource == 2) {
							echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
						}
					} else {
						echo $separator;
						if ($this->gradesource == 2) {
							echo $separator;
						}
					}
					/// add a column_feedback column        	
					if ($this->export_feedback) {
						if (!empty($this->selfgrade_items[$item->id]->display)) {
							echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
							if ($this->gradesource == 2) {
								echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
							}
						} else {
							echo $separator;
							if ($this->gradesource == 2) {
								echo $separator;
							}
						}	       		
					}
					
					/// add a column_counter column        	
					if ($this->export_counter) {
						if (!empty($this->selfgrade_items[$item->id]->display)) {
							echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
						} else {
							echo $separator;
						}	       		
					} 
				}
			}
			echo "\n";
			
			/// Print names of all the fields
			echo get_string("firstname").$separator.
				 get_string("lastname").$separator.
				get_string("email");

			foreach ($this->periods as $per) {
				foreach ($this->columns as $grade_item) {
					echo $separator;
					if ($this->gradesource == 2) {
						echo $separator;
					}

					/// add a feedback column
					if ($this->export_feedback) {
						echo $separator;
						if ($this->gradesource == 2) {
							echo $separator;
						}
					}
					
					/// add a counter column
					if ($this->export_counter) {
						echo $separator;
					}
				}
			}
			echo "\n";
			
			/// Print all the lines of data.
			$geub = new grade_export_update_buffer();
			$gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
			$gui->init();
			while ($userdata = $gui->next_user()) {

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
				if($roleid=="student")
				{
					$query2= 'select u.id as userid from {user} as u where u.id='.$USER->id.'';
					$usersgroupsids = $DB->get_records_sql( $query2 );
				}
				else if($roleid=="teacher")
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
					$query4= 'select ra.userid from {role_assignments} as ra, {role} as r where ra.contextid='.$context->id.' and r.archetype="student" and ra.roleid=r.id;';
					$usersgroupsids = $DB->get_records_sql( $query4 );
				}
				
				foreach ($usersgroupsids as $usergroup) {
					if($usergroup->userid==$user->id)
					{
				/////////////
				
						echo $user->firstname.$separator.$user->lastname.$separator.$user->email;
						
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
											$gradetxt = '';
										}
										
										echo $separator.$gradetxt;
										if ($this->export_feedback) {
											if ($gr != null && $gr->comment != null) {
												echo $separator.$gr->comment;
											} else {
												echo $separator;
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
											$gradetxt = '';
										}
										
										echo $separator.$gradetxt;
									
										if ($this->export_feedback) {
											if ($gr != null && $gr->comment != null) {
												echo $separator.$gr->comment;
											} else {
												echo $separator;
											}           			
										} 
										
									}
									
									$gr = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $per->id));
									if ($this->export_counter) {
										if ($gr != null && $gr->count != null) {
											echo $separator.$gr->count;
										} else {
											echo $separator;
										}
									}
								}  
							}
						echo "\n";
					}
				}
			}
		}
		else
		{
			/// Print names of the periods
			echo get_string('period','checkoutcome').$separator.
			$separator;
		
			foreach ($this->columns as $grade_item) 
			{
				if ($this->gradesource != 2) {
					echo $separator.$this->selected_period->name;
				
					// add a feedback column
					if ($this->export_feedback) {
						echo $separator.$this->selected_period->name;
					}
				} else {
					//teacher
					echo $separator.$this->selected_period->name; 		
					if ($this->export_feedback) {
						echo $separator.$this->selected_period->name;
					}
					//student
					echo $separator.$this->selected_period->name;
					if ($this->export_feedback) {
						echo $separator.$this->selected_period->name;
					}
				}
				
				// add a counter column
				if ($this->export_counter) {
					echo $separator.$this->selected_period->name;
				}
			}
			
			echo "\n";
		
			/// Print names of the outcomes
			echo get_string('outcome','checkoutcome').$separator.
			$separator;
		   
			
			foreach ($this->columns as $grade_item) {
				if ($this->gradesource != 2) {
					echo $separator.$this->format_column_name($grade_item);
				
					// add a feedback column
					if ($this->export_feedback) {
						echo $separator.$this->format_column_name($grade_item,false,true);
					}
				} else {
					//teacher
					echo $separator.$this->format_column_name($grade_item,false,false,'teacher');        		
					if ($this->export_feedback) {
						echo $separator.$this->format_column_name($grade_item,false,true,'teacher');
					}
					//student
					echo $separator.$this->format_column_name($grade_item,false,false,'student');
					if ($this->export_feedback) {
						echo $separator.$this->format_column_name($grade_item,false,true,'student');
					}
				}
				// add a counter column
				if ($this->export_counter) {
					echo $separator.$this->format_column_name($grade_item,true);
				}
			}
			echo "\n";
			
			/// Print category
			echo get_string('category','checkoutcome').$separator.
			$separator;
			
			foreach ($this->columns as $item) {
				if ($this->selfgrade_items[$item->id]->category != 0) {
					echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
					if ($this->gradesource == 2) {
						echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
					}
				} else {
					echo $separator;
					if ($this->gradesource == 2) {
						echo $separator;
					}
				}      	
					
				/// add a column_feedback column        	
				if ($this->export_feedback) {
					if ($this->selfgrade_items[$item->id]->category != 0) {
						echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
						if ($this->gradesource == 2) {
							echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
						}
					} else {
						echo $separator;
						if ($this->gradesource == 2) {
							echo $separator;
							
						}	        	
					}        	
				}
				
				/// add a column_counter column        	
				if ($this->export_counter) {
					if ($this->selfgrade_items[$item->id]->category != 0) {
						echo $separator.$this->categories[$this->selfgrade_items[$item->id]->category]->name;
					} else {
						echo $separator;        	
					}        	
				}
			}
			echo "\n";

			/// Print display
			echo get_string('display','checkoutcome').$separator.
			$separator;
			
			foreach ($this->columns as $item) {
				if (!empty($this->selfgrade_items[$item->id]->display)) {
				echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
					if ($this->gradesource == 2) {
						echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
					}
				} else {
					echo $separator;
					if ($this->gradesource == 2) {
						echo $separator;
					}
				}
				/// add a column_feedback column        	
				if ($this->export_feedback) {
					if (!empty($this->selfgrade_items[$item->id]->display)) {
						echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
						if ($this->gradesource == 2) {
							echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
						}
					} else {
						echo $separator;
						if ($this->gradesource == 2) {
							echo $separator;
						}
					}	       		
				}  
				
				/// add a column_counter column        	
				if ($this->export_counter) {
					if (!empty($this->selfgrade_items[$item->id]->display)) {
						echo $separator.$this->displays[$this->selfgrade_items[$item->id]->display]->name;
					} else {
						echo $separator;
					}	       		
				}
			}
			echo "\n";
			
			/// Print names of all the fields
			echo get_string("firstname").$separator.
				 get_string("lastname").$separator.
				get_string("email");

			foreach ($this->columns as $grade_item) {
				echo $separator;
				if ($this->gradesource == 2) {
					echo $separator;
				}

				/// add a feedback column
				if ($this->export_feedback) {
					echo $separator;
					if ($this->gradesource == 2) {
						echo $separator;
					}
				}
				
				/// add a counter column
				if ($this->export_counter) {
					echo $separator;
				}
			}
			echo "\n";

			/// Print all the lines of data.
			$geub = new grade_export_update_buffer();
			$gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
			$gui->init();
			while ($userdata = $gui->next_user()) {

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
				if($roleid=="student")
				{
					$query2= 'select u.id as userid from {user} as u where u.id='.$USER->id.'';
					$usersgroupsids = $DB->get_records_sql( $query2 );
				}
				else if($roleid=="teacher")
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
					$query3= 'select ra.userid from {role_assignments} as ra, {role} as r where ra.contextid='.$context->id.' and r.archetype="student" and ra.roleid=r.id;';
					$usersgroupsids = $DB->get_records_sql( $query3 );
				}
			
				foreach ($usersgroupsids as $usergroup) {
					if($usergroup->userid==$user->id)
					{
				/////////////
				
						echo $user->firstname.$separator.$user->lastname.$separator.$user->email;
						
							//foreach ($userdata->grades as $itemid => $grade) {
							foreach ($this->columns as $item) {
								if ($this->gradesource == 0 || $this->gradesource == 2) {							
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
										$gradetxt = '';
									}
									
									echo $separator.$gradetxt;
									if ($this->export_feedback) {
										if ($gr != null && $gr->comment != null) {
											echo $separator.$gr->comment;
										} else {
											echo $separator;
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
										$gradetxt = '';
									}
									
									echo $separator.$gradetxt;
								
									if ($this->export_feedback) {
										if ($gr != null && $gr->comment != null) {
											echo $separator.$gr->comment;
										} else {
											echo $separator;
										}           			
									} 
									
								}
								
								$gr = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem' => $item->checkoutcomeitem, 'userid' => $user->id, 'period' => $this->selected_period->id));
								if ($this->export_counter) {
									if ($gr != null && $gr->count != null) {
										echo $separator.$gr->count;
									} else {
										echo $separator;
									}
								}
							}            
						echo "\n";
					}
				}
			}
		}	
        
        
        $gui->close();
        $geub->close();

        exit;
    }    
   
    
}


