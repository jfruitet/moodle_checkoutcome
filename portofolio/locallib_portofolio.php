<?php

require_once($CFG->dirroot.'/lib/pdflib.php');
require_once($CFG->libdir . '/portfolio/caller.php');
require_once ($CFG->dirroot.'/mod/checkoutcome/pdf/checkoutcome_pdf.php');

/**
 * This class enables data transfer to portfolios like Mahara
 * @author 2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 *
 */
class checkoutcome_portfolio_caller extends portfolio_module_caller_base {
		
	protected $checkoutcomeid;
	protected $userid;
	protected $selected_periodid;
	protected $categoryid;
	protected $ispdffile;
	protected $contextid;
	
	
	private $items;
	private $category;
	private $categories;
	private $displays;
	private $documents;
	private $pdffile;
	private $checkoutcome;
	private $selected_period;
	
	
	private $keyedfiles; // just using multifiles isn't enough if we're exporting a full thread	
	
	/**
	 * Return an array where the key is the arg name and the value is a boolean
	 * which value is true if the arg is expected, else false
	 * @return multitype:boolean
	 */
	public static function expected_callbackargs() {
	return array(
			'checkoutcomeid' => true,
			'userid' => true,
			'selected_periodid' => true,
			'contextid' => true,
			'categoryid'	=> false,		
			'ispdffile'   => false,
			
	);
	}
	
	/**
	 * Default constructor
	 * @param Array $callbackargs
	 */
	function __construct($callbackargs) {
		global $DB;
    	parent::__construct($callbackargs);
    	$this->checkoutcome = $DB->get_record('checkoutcome', array('id' => $this->checkoutcomeid));
    	$this->cm = get_coursemodule_from_instance('checkoutcome', $this->checkoutcomeid);    	
    	
    }
    
    /**
     * (non-PHPdoc)
     * @see portfolio_caller_base::load_data()
     */
    public function load_data() {
    	global $DB, $CFG;
    
    	if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }

    	$fs = get_file_storage();
    	$files = array();
    
    	if ($this->ispdffile) {
    		$fs = get_file_storage();
    		$fileid = $this->generatePDF();
    		$files = $this->pdffile = $fs->get_file_by_id($fileid);
    	} else if ($this->categoryid != null) {
    		// fill in $this->items with validated items of the category
    		$this->get_validateditems($this->categoryid);
    
    		$this->documents = array();
    		if ($this->items != null) {
    			foreach ($this->items as $item) {
	    			if ($item[2] != null) {
    					$itemfiles = array();
	    				// check if there are attached files
	    				$this->documents = $DB->get_records('checkoutcome_document' ,array('gradeid' => $item[2]->id));
	    				if (!empty($this->documents)) {
	    					// get files objects
	    					foreach ($this->documents as $doc) {
	    						$itemfiles[] = $fs->get_file_by_hash($fs->get_pathname_hash($this->context->id,'mod_checkoutcome', 'document', $doc->id, '/', $doc->title));
	    					}
	    				}
	    				if (!empty($itemfiles)) {
	    					$this->keyedfiles[$item[1]->id] = $itemfiles;
	    					$files = array_merge($itemfiles,$files);
	    				}
	    			}
    			}
    		}
    	} else {
    		throw new portfolio_caller_exception('mustprovidecategoryorpdffile', 'checkoutcome');
    	}
    		
    	if (!empty($files)) {
    		$this->set_file_and_format_data($files);
    	}
    
    	if (empty($this->multifiles) && !empty($this->singlefile)) {
    		$this->multifiles = array($this->singlefile); // copy_files workaround
    	}
    	
    	// force rich HTML to avoid question about export format
    	$this->add_format(PORTFOLIO_FORMAT_RICHHTML);
    }
    
    /**
     * (non-PHPdoc)
     * @see portfolio_module_caller_base::get_return_url()
     */
    function get_return_url() {
    	global $CFG;
    	return $CFG->wwwroot . '/mod/checkoutcome/view.php?id='.$this->cm->id.'&selected_periodid='.$this->selected_periodid;
    }
    
    /**
     * (non-PHPdoc)
     * @see portfolio_module_caller_base::get_navigation()
     */
    function get_navigation() {
    	global $CFG;
    
    	$navlinks = array();
    	$navlinks[] = array(
    			'name' => format_string($this->checkoutcome->name),
    			'link' => $CFG->wwwroot . '/mod/checkoutcome/view.php?id=' . $this->cm->id,
    			'type' => 'title'
    	);
    	return array($navlinks, $this->cm);
    }
    
    /**
     * (non-PHPdoc)
     * @see portfolio_caller_base::prepare_package()
     */
    function prepare_package() {
    	global $CFG,$USER;
    
    	// set up the leap2a writer if we need it
    	$writingleap = false;
    	if ($this->exporter->get('formatclass') == PORTFOLIO_FORMAT_LEAP2A) {
    		$leapwriter = $this->exporter->get('format')->leap2a_writer();
    		$writingleap = true;
    	}
    	if ($this->ispdffile) { // simplest case first - single pdf file
    		//$this->copy_files(array($this->singlefile), $this->ispdffile);
    		$this->copy_files($this->multifiles);
    		if ($writingleap) { // if we're writing leap, make the manifest to go along with the file
    			$entry = new portfolio_format_leap2a_file($this->singlefile->get_filename(), $this->singlefile);
    			$leapwriter->add_entry($entry);
    			return $this->exporter->write_new_file($leapwriter->to_xml(), $this->exporter->get('format')->manifest_name(), true);
    		}
    	} else if ($this->categoryid != null) {  // exporting validated items from category
    		$content = ''; // if we're just writing HTML, start a string to add each post to
    		$ids = array(); // if we're writing leap2a, keep track of all entryids so we can add a selection element
    		$time = time();
    		$index = 0;
    		foreach ($this->items as $item) {
    			$itemhtml =  $this->prepare_item($item);
    			if ($writingleap) {
    				$time -= $index;
    				$ids[] = $this->prepare_post_leap2a($leapwriter, $item, $itemhtml, $time);
    			} else {
    				$content .= $itemhtml . '<br /><br />';
    			}
    			$index++;
    		}
    		$this->copy_files($this->multifiles);
    		$name = 'validateditems_'.$this->selected_period->name.'_category_'.$this->category->name. '.html';
    		$manifest = ($this->exporter->get('format') instanceof PORTFOLIO_FORMAT_RICH);
    		if ($writingleap) {
    			// add on an extra 'selection' entry
    			if ($this->categoryid != 0) {
    				$selection = new portfolio_format_leap2a_entry('checkoutcomecategory' . $this->categoryid,
    						$this->checkoutcome->name .' : '.$this->selected_period->name.' : ' . $this->category->name . ' (' .userdate($time, '', $USER->timezone) . ')', 'selection');
    			} else {
    				$selection = new portfolio_format_leap2a_entry('checkoutcomecategory' . $this->categoryid,
    						$this->checkoutcome->name . ' : ' .$this->selected_period->name. ' (' .userdate($time, '', $USER->timezone) . ')', 'selection');
    			}    			
    			$selection->author = $USER;
    			$leapwriter->add_entry($selection);
    			$leapwriter->make_selection($selection, $ids, 'Grouping');
    			$content = $leapwriter->to_xml();
    			$name = $this->get('exporter')->get('format')->manifest_name();
    		}
    		$this->get('exporter')->write_new_file($content, $name, $manifest);
    	}
    }
    
    /**
     * Prepares leap2a entry and adds it to the writer
     * @param portfolio_format_leap2a_writer $leapwriter
     * @param Array $item
     * @param String $itemhtml
     * @param Integer $time
     */
    private function prepare_post_leap2a(portfolio_format_leap2a_writer $leapwriter, $item, $itemhtml, $time) {
    	global $DB;
    	$entry = new portfolio_format_leap2a_entry('checkoutcomeitem' . $item[1]->id,  $item[0]->itemname, 'resource', $itemhtml);
    	$entry->published = $entry->updated = $time;
    	$author = $DB->get_record('user', array('id' => $item[3]->usermodified));
    	$entry->author = $author;
    	if (is_array($this->keyedfiles) && array_key_exists($item[1]->id, $this->keyedfiles) && is_array($this->keyedfiles[$item[1]->id])) {
    		$leapwriter->link_files($entry, $this->keyedfiles[$item[1]->id], 'checkoutcomeitem' . $item[1]->id . 'attachment');
    	}
    	$entry->add_category('web', 'resource_type');
    	$leapwriter->add_entry($entry);
    	return $entry->id;
    }
    
    /**
     * Copy given files to the exporter
     * @param Array of Stored_file $files
     * @param Boolean $justone
     */
    private function copy_files($files, $justone=false) {
    	if (empty($files)) {
    		return;
    	}
    	foreach ($files as $f) {
    		if ($justone && $f->get_id() != $justone) {
    			continue;
    		}
    		$this->get('exporter')->copy_existing_file($f);
    		if ($justone && $f->get_id() == $justone) {
    			return true; // all we need to do
    		}
    	}
    }
    
    /**
     * Prepares body of the exported item
     * @param Array $item
     * @param unknown_type $fileoutputextras
     */
    private function prepare_item($item, $fileoutputextras=null) {
    	global $DB;
    	    
    	$viewfullnames = true;
    		
    	// format the item body
    	$options = portfolio_format_text_options();
    	$format = $this->get('exporter')->get('format');
    		
    	//get outcome title
    	$title = $item[0]->itemname;
    		
    	$text = '';
    	//print finalgrade
    	if($item[3]->grade) {
    		
    		$scale = $DB->get_record('scale', array('id' => $item[0]->scaleid));
    		$scaleitems = mb_split(',',$scale->scale);
    		$finalegrade = $scaleitems[intval($item[3]->grade)-1];
    		
    		$text .= '<div>' . get_string('finalgrade','checkoutcome');
    		$text .= $finalegrade . '</div><br/>';
    		
    	}
    	// Print counter
    	if ($item[1]->countgoal != 0) {
    	$text .= '<div>';    	
    		$count = 0;
    		if ($item[2] != null) {
    			$count = $item[2]->count;
    		}
    		$text .= '<span>'.$count.'</span>';
    		$text .='<span>/'.$item[1]->countgoal.'</span>';
    	$text .= '</div>';
    	}    	
    	// End Print counter
    	// print teacher feedback if existing
    	if ($item[3]->comment) {
    		$text .= '<div style="max-width: 500px;word-wrap: break-word;" class="comment">' . get_string('teacher_feedback','checkoutcome') . '<br/>';
    		$text .= $item[3]->comment . '</div><br/>';
    	}
    	//print student comment if existing
    	if ($item[2] != null && $item[2]->comment) {
    		$text .= '<div style="max-width: 500px;word-wrap: break-word;" class="comment">' . get_string('student_comment','checkoutcome') . '<br/>';
    		$text .= $item[2]->comment . '</div><br/>';
    	}
    		
    	$formattedtext = format_text($text, FORMAT_HTML, $options, $this->get('course')->id);
    	$formattedtext = portfolio_rewrite_pluginfile_urls($formattedtext, $this->contextid, 'mod_checkoutcome', 'item', $item[1]->id, $format);
    
    	$output = '<table border="0" cellpadding="3" cellspacing="0" class="forumpost">';
    
    	// Header
	    	$output .= '<tr class="header">';
	    	$output .= '<td class="validateditem">';
	    	//$output .= '<div class="subject">'.format_string($title).'</div>';
	    		
	    	// nom de l'enseignant qui a validé
	    	$teacher = $DB->get_record('user', array('id' => $item[3]->usermodified));
	    	$fullname = fullname($teacher, $viewfullnames);
	    	$by = new stdClass();
	    	$by->name = $fullname;
	    	$by->date = userdate($item[3]->timemodified, '', $teacher->timezone);
	    
	    	$output .= '<div class="validator">'.format_string(get_string('validatedbynameondate', 'checkoutcome', $by)).'</div>';
	    	$output .= '</td>';
	    	$output .= '</tr>';
    		
    	// Content
	    	$output .= '<tr>';
	    	$output .= '<td class="content">';
	    	$output .= $formattedtext;    
	    	$output .= '</td>';
	    	$output .='</tr>';
    	$output .= '</table>'."\n\n";
    
    	return $output;
    }
    
    /**
     * (non-PHPdoc)
     * @see portfolio_caller_base::get_sha1()
     */
    function get_sha1() {
    	$filesha = '';
    	try {
    		$filesha = $this->get_sha1_file();
    	} catch (portfolio_caller_exception $e) {
    	} // no files
    
    	if ($this->ispdffile) {
    		return sha1($filesha . ',' . $this->checkoutcome->name);
    	} else {
    		$sha1s = array($filesha);
    		if ($this->items != null) {
    			foreach ($this->items as $item) {
    				$sha1s[] = sha1($this->checkoutcome->name . ',' . $item[0]->itemname);
    			}
    			return sha1(implode(',', $sha1s));
    		} else {
    			return null;
    		}
    			
    	}
    }
    
    /**
     * (non-PHPdoc)
     * @see portfolio_caller_base::expected_time()
     */
    function expected_time() {
    	$filetime = $this->expected_time_file();
    		
    	if (!empty($this->items)) {
    		$itemtime = portfolio_expected_time_db(count($this->items));
    		if ($filetime < $itemtime) {
    			return $itemtime;
    		}
    	}
    	return $filetime;
    }
    
    /**
     * (non-PHPdoc)
     * @see portfolio_caller_base::check_permissions()
     */
    function check_permissions() {
		global $CFG;

    	if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }
    		
    	return (has_capability('mod/checkoutcome:updateown', $context));
    
    }
    
    /**
     * Returns module name
     * @return Ambigous <string, lang_string, unknown>
     */
    public static function display_name() {
    	return get_string('modulename', 'checkoutcome');
    }
    
    /**
     * Returns an array of formats supported
     */
    public static function base_supported_formats() {
    	return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_RICHHTML, PORTFOLIO_FORMAT_PLAINHTML, PORTFOLIO_FORMAT_LEAP2A);
    }    
    
     /**
     * Get an array of the validated items corresponding to the targeted category
     * @param Integer $categoryid
     * @throws portfolio_caller_exception
     */
    function get_validateditems($categoryid) {
    	global $DB;
    	
    	// Getting the selected period
    	if ($this->selected_periodid) {
    		$this->selected_period = $DB->get_record('checkoutcome_periods', array('id' => $this->selected_periodid));
    	}
    	 
    	if (!$this->selected_period) {
    		throw new portfolio_caller_exception('periodnotfound','checkoutcome');
    	}
    	
    	if ($categoryid == 0) {
    		// Add category NA
    		$categNA = new stdClass();
    		$categNA->id = 0;
    		$categNA->name = get_string('no_category_name','checkoutcome');
    		$categNA->description = get_string('no_category_desc','checkoutcome');
    		$this->category = $categNA;
    	} else {    	
	    	//Getting category    	
	    	if (!$this->category = $DB->get_record('checkoutcome_category', array('id' => $categoryid))) {
	    		throw new portfolio_caller_exception('invalidcategoryid', 'checkoutcome');
	    	}
    	}
    
    	$this->items = NULL;    	     	 
    	 
    	// getting checkoutcome items of the category sorted by shortname
    	$sql = 'select ch.id from {checkoutcome_item} as ch,{grade_items} as g, {grade_outcomes} as go where ch.category = ? and ch.gradeitem = g.id and g.outcomeid = go.id and g.itemmodule = ? and g.iteminstance = ? order by ch.category,go.shortname';
    	
    	$ch_ids = $DB->get_records_sql($sql,array($this->category->id, $this->cm->modname, $this->cm->instance));
    	    
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
    
    		//get self grades if existing
    		if ($sg_item = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem'=>$ch_item->id, 'userid' => $this->userid, 'period' => $this->selected_periodid))) {
    			$item[2] = $sg_item;
    		}
    
    		//get grades if existing
    		if ($grades = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem'=>$ch_item->id, 'userid' => $this->userid, 'period' => $this->selected_periodid))) {
    			$item[3] = $grades;
    		}

    		// Add only validated items
    		if ($this->isItemValidated($item)) {
    			$this->items[$ch_item->id]= $item;
    		}
    	}

    	if ($this->items == null) {
    		throw new portfolio_caller_exception('emptyitemlist','checkoutcome');
    	}
    	
    	// Getting displays of the module instance
    	$this->displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $this->checkoutcomeid),'id');
    
    }

    /**
     * Get an array of the grade items corresponding to the outcomes of the course
     * @throws portfolio_caller_exception
     */
    function get_items() {
    	global $DB;      	 
    	
    	// Getting the selected period
    	if ($this->selected_periodid) {
    		$this->selected_period = $DB->get_record('checkoutcome_periods', array('id' => $this->selected_periodid));
    	} 
    	
    	if (!$this->selected_period) {
    		throw new portfolio_caller_exception('periodnotfound','checkoutcome');
    	}    	
    	
    	// Getting categories
    	$this->categories = $DB->get_records('checkoutcome_category',array('checkoutcome' => $this->checkoutcome->id),'id');
    	 
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
    	 
    	// Add category NA to the end of categories array
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
    
    			//get self grades if existing
    			if ($sg_item = $DB->get_record('checkoutcome_selfgrading', array('checkoutcomeitem'=>$ch_item->id, 'userid' => $this->userid, 'period' => $this->selected_periodid))) {
    				$item[2] = $sg_item;
    			}
    
    			//get grades if existing
    			if ($grades = $DB->get_record('checkoutcome_teachergrading', array('checkoutcomeitem'=>$ch_item->id, 'userid' => $this->userid, 'period' => $this->selected_periodid))) {
    				$item[3] = $grades;
    			}
    			 
    			$this->items[$ch_item->id]= $item;
    		}
    	}
    	
    	if ($this->items == null) {
    		throw new portfolio_caller_exception('emptyitemlist','checkoutcome');
    	}
    	 
    	// Getting displays
    	$this->displays = $DB->get_records('checkoutcome_display',array('checkoutcome' => $this->checkoutcome->id),'id');
    
    }
    
    /**
     * Returns true if item is validated, else false
     * @param Array $item
     * @return boolean
     */
    function isItemValidated($item) {
    	global $DB;
    	//$scale = $DB->get_record('scale', array('id' => $item[0]->scaleid));
    	//$scaleitems = mb_split(',',$scale->scale);
    	// item is validated if the teacher has graded with the highest scale item level    	 
    		// change done on 8th of August 2012 : item is validated if teacher has graded
    	if (!empty($item[3]->grade) && $item[3] != -1 /*&& intval($item[3]->finalgrade) == (count($scaleitems))*/) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
    /**
     * 
     * @param grade_grades $grade
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
     * Generate PDF file, stores it and returns its id
     * @throws portfolio_caller_exception
     * @return number|NULL
     */
    function generatePDF() {
    	global $USER,$DB,$CFG;
    	
    	$this->get_items();    	
    	
    	$username = $USER->firstname.' '.$USER->lastname;
    	
    	// Get goal of the period if existing
    	$goalid  = optional_param('goalid', 0, PARAM_INT);
    	if ($goalid) {
    		$goal = $DB->get_record('checkoutcome_period_goals', array('id' => $goalid));
    	}
    	 
    	// create new PDF document
    	$pdf = new checkoutcome_pdf('P', 'mm', 'A4', true, 'UTF-8',$this->checkoutcome->name, $username);
    	
    	// set document information
    	$pdf->SetCreator('Université de Nantes');
    	$pdf->SetAuthor($username);
    	$pdf->SetTitle($this->checkoutcome->name);
    	$pdf->SetSubject($this->checkoutcome->intro);
    	$pdf->SetDisplayMode(50);
    	
    	
    	//$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
    	
    	// set default header data
    	$pdf->SetHeaderData('/mod/checkoutcome/pix/logo.png', 30, $this->checkoutcome->name, $username);
    	
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
    	
    	//set some language-dependent strings
    	$pdf->setLanguageArray($USER->lang);
    	
    	// ---------------------------------------------------------
    	
    	// add first page
    	$pdf->AddPage();
    	$pdf->Ln(80);
    	
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
    	$period_dates = ' ('.date('d M Y',$this->selected_period->startdate).' - '.date('d M Y',$this->selected_period->enddate).')';
    	$pdf->Write(0, $period_dates, '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
    	$pdf->Ln(10);
    	
    	// Student name
    	$pdf->SetFont('helvetica', 'B', 20);
    	$pdf->Write(0, '- '.get_string('author','checkoutcome').$username.' -', '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
    	$pdf->Ln(60);
    	
    	// Date
    	$date = date('d-M-Y',time());
    	$pdf->SetFont('helvetica', 'B', 10);
    	$pdf->Write(0, get_string('date_pdf','checkoutcome').$date, '', 0, 'C', true, 0, false, true, 0, 0, array(20,20));
    	

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
    		$pdf->Ln(20);
    			
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
    	
    			// Change or not current catgeory and display category title, begin table
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
    				$categoryContent .= ' <span style="font-size:70%;">['.date('d-M-Y H:i',$item[2]->commenttime).']</span>';
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
    						$categoryContent .= '<a href="'.$efile.'" style="color:inherit;">'.$doc->title .'</a> <span style="font-size:70%;">[' . date('d-M-Y H:i',$doc->timemodified) . ']</span> ';
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
    				$categoryContent .= ' <span style="font-size:70%;">['.date('d-M-Y H:i',$feedbackhisto->timecreated).']</span>';
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
    	
    	$time = time();
    	$filename = $this->checkoutcome->name.'_'.$username.'_'.$this->selected_period->name.'_'.date('d-M-Y H:i',$time).'.pdf';
    	$pathname = $CFG->dataroot.'/temp/'.$filename;
    	
    	//Close and output PDF document
    	$pdf->Output($pathname, 'F');
    	
    	$file_record = new stdClass();
    	
    	if (is_number($this->contextid)) {
    		$file_record->contextid = $this->contextid;
    	} else {
    		$file_record->contextid = intval($this->contextid);
    	}    	
    	$file_record->component = 'mod_checkoutcome';
    	$file_record->filearea  = 'exportpdf';
    	$file_record->itemid = 0;
    	$file_record->filepath  = '/';
    	$file_record->filename  = $filename;
    	
    	$file_record->timecreated  = $file_record->timemodified = $time;    	
    	$file_record->mimetype     = 'application/pdf';
    	$file_record->userid       = $this->userid;
    	$file_record->author       = $username;
    	$file_record->license      = 'allrightsreserved';
    	$file_record->sortorder    = 0;
    	
    	if ($file = get_file_storage()->create_file_from_pathname($file_record, $pathname)) {
    		unlink($pathname);
    		return $file->get_id();    		
    	} else {
    		unlink($pathname);
    		throw new portfolio_caller_exception('errorstoringpdffile', 'checkoutcome');
    		return null;
    	}    	
    }	
	
}

