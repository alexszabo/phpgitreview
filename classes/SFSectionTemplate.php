<?php
/***************************************************************
 * @author     Alexander Szabo | http://www.alexszabo.de
 * @created    02.01.2007
 * @license    Creative Commons Attribution 3.0 Germany License    
               http://creativecommons.org/licenses/by/3.0/de/
 ***************************************************************/
require_once('SFTemplate.php');

class SFSectionTemplate extends SFTemplate
{
	var $_sectionprefix = "internal_donotuse_section_";
	var $_sections = array();
	//------------------------------------------------------------------
	// Constructor
	//------------------------------------------------------------------
	function SFSectionTemplate($filename) {
		$this->SFTemplate($filename);
		$this->SplitTemplateIntoSections();
	}

	//------------------------------------------------------------------
	// Methods
	//------------------------------------------------------------------
	function SetSection($name, $template) {
		$this->_sections[$name] = array(
				"template" => $template,
				"html" => ""
			);
	}
	
	//------------------------------------------------------------------
	function SplitTemplateIntoSections() {
		$temp = $this->_template;
		do {
			$count = preg_match_all('@<!--\s*SECTION:([a-zA-Z0-9_]*)\s*-->@', $temp, $pos, PREG_OFFSET_CAPTURE);
			if ($count > 0) {
				$posstart = $pos[0][$count-1][1];
				$sectionname = $pos[1][$count-1][0];
				$innerstart = $posstart + strlen($pos[0][$count-1][0]);
				$count = preg_match('@<!--\s*ENDSECTION\s*-->@', $temp, $pos, PREG_OFFSET_CAPTURE, $posstart);
				if ($count > 0) {
					$innerend = $pos[0][1];
					$posend = $innerend + strlen($pos[0][0]);
					
					$innertemplate = substr($temp, $innerstart, $innerend-$innerstart);
					$this->SetSection($sectionname, $innertemplate);
					
					$temp = substr($temp, 0, $posstart)."{#".$this->_sectionprefix.$sectionname."#}".substr($temp, $posend, strlen($temp)-$posend);
				} else
					throw new Exception("ERROR: SFSectionTemplate encountered unclosed section '".$sectionname."'.");
			}
		} while ($count != 0);
		$this->_template = $temp;
	}
	
	//------------------------------------------------------------------
	function ParseSection($sectionname) {
		if (isset($this->_sections[$sectionname])) {
			$template = $this->_sections[$sectionname]["template"];
			$template = $this->FillTemplateWithVars($template);
			$this->_sections[$sectionname]["html"] .= $template;
		}
	}
	
	//------------------------------------------------------------------
	function GetVar($varname) {
		if (is_array($varname))	$varname = $varname[1];

		if (substr($varname, 0, strlen($this->_sectionprefix)) == $this->_sectionprefix) {
			$section = substr($varname, strlen($this->_sectionprefix));
			$result = $this->_sections[$section]["html"];
			$this->_sections[$section]["html"] = "";
			return $result;
		} else {
			return parent::GetVar($varname);
		}
	}	
	
	//------------------------------------------------------------------
	function GetSection($section) {
		return $this->_sections[$section]["html"];
	}	
	
	//------------------------------------------------------------------
	function Reset() {
		foreach ($this->_sections as $sectionname => $section) {
			$this->_sections[$sectionname]["html"] = "";
		}
	}

	//------------------------------------------------------------------
	function ResetSection($sectionname) {
		if (isset($this->_sections[$sectionname])) {
			$this->_sections[$sectionname]["html"] = "";
		}
	}
	
	//------------------------------------------------------------------
	//------------------------------------------------------------------
}
?>