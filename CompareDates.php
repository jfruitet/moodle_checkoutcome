<?php

require_once 'HTML/QuickForm/Rule/Compare.php';

/**
 * Rule to compare two date form fields
 *
 */
class HTML_QuickForm_Rule_Compare_Dates extends HTML_QuickForm_Rule_Compare
{
	function validate($values, $operator = null)
	{
		$operator = $this->_findOperator($operator);
		$newvalues = array();
		if (is_array($values[0])) {
			$newvalues = $this->stringToTime($values);
		} else {
			$newvalues = $values;
		}
		if ('==' != $operator && '!=' != $operator) {
			$compareFn = create_function('$a, $b', 'return floatval($a) ' . $operator . ' floatval($b);');
		} else {
			$compareFn = create_function('$a, $b', 'return $a ' . $operator . ' $b;');
		}
	
		return $compareFn($newvalues[0], $newvalues[1]);
	}
	
	function stringToTime($values) {
		$newvalues = array();
		$startdatetime = strtotime($values[0]['year'].'-'.$values[0]['month'].'-'.$values[0]['day']);
		$enddatetime = strtotime($values[1]['year'].'-'.$values[1]['month'].'-'.$values[1]['day']);		 
		$newvalues[]=$startdatetime;
		$newvalues[]=$enddatetime;		
		return $newvalues;
	}
}
