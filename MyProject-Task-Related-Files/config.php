<?php
/** 
* Basic configuration file/class for passing through other pages to protecting Cross Site Scripting(XSS) Attack
*/

class Config {
	
	// Filter user input for protecting Cross Site Scripting(XSS) Attack
	public function filter_input($value)
	{
		$value = trim($value);
		$value = stripslashes($value);
		$value = htmlspecialchars($value);
		return $value;
	}
}