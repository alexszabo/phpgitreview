<?php
/***************************************************************
 * @author     Alexander Szabo | http://www.alexszabo.de
 * @created    25.10.2006
 * @license    Creative Commons Attribution 3.0 Germany License    
               http://creativecommons.org/licenses/by/3.0/de/
 ***************************************************************/
class SFTemplate
{
	var $_template;
	var $_values = Array();
	
	//------------------------------------------------------------------
	// Constructor
	//------------------------------------------------------------------
	function SFTemplate($filename) {
		$this->_template = "";
		if (file_exists($filename)) {
			$this->_template = file_get_contents($filename, false);
		} else {
			throw new Exception("ERROR: Unable to locate file '".$filename."'.");
		}
	}

	//------------------------------------------------------------------
	// Methods
	//------------------------------------------------------------------
	function GetVar($varname) {
		if (is_array($varname))	$varname = $varname[1];
		if (preg_match('/^[a-zA-Z0-9_]*$/',$varname) < 1)
			return "ERROR: Template Variable '$varname' has wrong format.";
		if (isset($this->_values["$varname"])) {
			return $this->_values["$varname"];
		} else {
			return "";
		}
	}
	
	//------------------------------------------------------------------
	function SetVar($varname, $value) {
		if (preg_match('/^[a-zA-Z0-9_]*$/',$varname) < 1)
			die("ERROR: Template Variable '".$varname."' has wrong format.");
//		if (preg_match('/\{#([^\{\}]*)#\}/',$value) >= 1)
//			throw new Exception("ERROR: Value contains forbidden string '{#...#}' for Variable '".$varname."'.");
		$this->_values[$varname] = $value;
	}	
	
	//------------------------------------------------------------------
	function FillTemplateWithVars($template) {
		$template = preg_replace_callback('/\{#([^\{\}]*)#\}/', array($this,"GetVar"), $template);
		return $template;
	}
	//------------------------------------------------------------------
	function Get() {
		$result = $this->_template;
		$result = $this->FillTemplateWithVars($result);
		return $result;
	}
}
?>