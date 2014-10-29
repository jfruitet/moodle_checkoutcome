<?php
require_once($CFG->dirroot.'/lib/pdflib.php');


/**
 * This class extends moodle pdf class to enable customization
 * @author 2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 *
 */
class checkoutcome_pdf extends pdf {
	
	var $title;
	var $author;
	
	
	/**
	 * Class constructor
	 * See the parent class documentation for the parameters info.
	 */
	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $title, $author) {
	
		parent::__construct($orientation, $unit, $format, $unicode, $encoding);
		
		$this->title = $title;
		$this->author = $author;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see TCPDF::Header()
	 */
 	public function Header() {
 		global $CFG;
 		// Logo
 		$image_file = $CFG->wwwroot .'/mod/checkoutcome/pix/logo.png';
 		$this->Image($image_file, 15, 5, 20, '', 'PNG', '', 'M', false, 300, '', false, false, 0, false, false, false);
		$this->SetX(55);
 		// Title
 		$this->SetFont('helvetica', 'B', 12);
 		$this->Cell(100, 15, $this->title, 0, false, 'C', 0, '', 0, false, 'M', 'M');

 		// Author
 		$this->SetFont('helvetica', '', 12);
 		$this->Cell(40, 15, $this->author, 0, false, 'R', 0, '', 0, false, 'M', 'M');

 		$this->Line(15, 18, 195, 18);
 	}
	
	/**
	 * (non-PHPdoc)
	 * @see TCPDF::Footer()
	 */
	public function Footer() {
		// Position at 15 mm from bottom
		$this->SetY(-15);
		// Set font
		$this->SetFont('helvetica', 'I', 8);
		// Page number
		$this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
		
		$this->Line(15, 282, 195, 282);
	}	
	
	
}
