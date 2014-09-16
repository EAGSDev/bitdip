<?php

defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * @package Search
 * @subpackage Options
 */

class searchOption
{
	public $label;
	public $htmlName;
	public $value;

	public $locked=false;
	public $checked=false;

	function __construct($htmlName, $label, $value)
	{
		$this->htmlName=$htmlName;
		$this->label=$label;
		$this->value=$value;
	}
}

class searchOptionSelect extends searchOption
{
	function formHTML()
	{
		return '<option value="'.$this->value.'" '.($this->checked?'selected ':'').'>'.l_t($this->label).'</option>';
	}
}
class searchOptionCheckbox extends searchOption
{
	function __construct($htmlName, $label, $value)
	{
		parent::__construct($htmlName,$label,$value);
		$this->htmlName .= '[]';
	}

	function formHTML()
	{
		// Disabled stops the input being sent via the form
		// Readonly isn't actually read-only for checkboxes and radios
		// So if the checkbox is locked we have to set it to disabled and enter the data via a hidden field instead..
		$output = "";

		$output .= '<input type="checkbox"
			value="'.$this->value.'"
			'.($this->locked?'':'name="'.$this->htmlName.'"').'
			'.($this->checked?'checked ':'').'
			'.($this->locked?'disabled ':'').'/>
			'.l_t($this->label);

		if($this->locked)
			$output .= ' <input type="hidden" name="'.$this->htmlName.'" value="'.$this->value.'" />';

		return $output;
	}
}
class searchOptionRadio extends searchOption
{
	function formHTML()
	{
		$output = "";
		$output .= '<input type="radio"
			value="'.$this->value.'"
			'.($this->locked?'':'name="'.$this->htmlName.'"').'
			'.($this->checked?'checked ':'').'
			'.($this->locked?'disabled ':'').'/>
			'.l_t($this->label);

		if($this->locked)
			$output .= ' <input type="hidden" name="'.$this->htmlName.'" value="'.$this->value.'" />';

		return $output;
	}
}
?>