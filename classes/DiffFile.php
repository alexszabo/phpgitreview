<?php
require_once 'DiffSection.php';

class DiffFile {
	
	private $a_filename = "";
	private $b_filename = "";
	private $isnewfile = false;
	private $isdeletedfile = false;
	private $sections = array();
	private $isBinary = false;

	public function __construct($lines) {
		if (defined("DEBUG") && (DEBUG))
			$originallines = $lines;
		
		//parse first line 
		list($line, $lines) =  explode("\n", $lines."\n", 2);
		
		$matches = array();
		if (preg_match_all('#^diff \-\-git a/(.*) b/(.*)$#', $line, $matches)) {
			$this->a_filename = $matches[1][0];
			$this->b_filename = $matches[2][0];
		} else {
			if (defined("DEBUG") && (DEBUG))
				echo "<pre>".htmlspecialchars($originallines)."</pre>";
			throw new Exception("Expected a line starting with 'diff --git' but found '".$line."'.");
		}
		
		//do not process empty diff
		if (trim($lines) == "")	return;
		
		//parse optinal second line and third line
		list($line, $lines) =  explode("\n", $lines."\n", 2);
		if (substr($line, 0, 8) == 'new file') {
			$this->isnewfile = true;
			list($line, $lines) =  explode("\n", $lines."\n", 2); //get next line 
		}
		if (substr($line, 0, 12) == 'deleted file') {
			$this->isdeletedfile = true;
			list($line, $lines) =  explode("\n", $lines."\n", 2); //get next line 
		}
		
		//just expect it .. we do not care about the value
		if (substr($line, 0, 5) != 'index') {
			if (defined("DEBUG") && (DEBUG))
				echo "<pre>".htmlspecialchars($originallines)."</pre>";
			throw new Exception("Unable to understand the second line: '".$line."'.");
		}
		
		
		list($line, $lines) =  explode("\n", $lines."\n", 2);
		
		//skip binary files
		if (substr($line, 0, 11) == 'Binary file') {
			$this->isBinary = true;
			return;
		}
		
		//some files have no changes but appear in the diff??
		if ((trim($line) == "") && (trim($lines) == "")) {
			return;
		}
		
		//parse --- line
		if (substr($line, 0, 4) != '--- ') {
			if (defined("DEBUG") && (DEBUG))
				echo "<pre>".htmlspecialchars($originallines)."</pre>";
			throw new Exception("Expected a line starting with '---' but found: '".$line."'.");
		}

		//parse +++ line
		list($line, $lines) =  explode("\n", $lines."\n", 2);
		if (substr($line, 0, 4) != '+++ ') {
			if (defined("DEBUG") && (DEBUG))
				echo "<pre>".htmlspecialchars($originallines)."</pre>";
			throw new Exception("Expected a line starting with '+++' but found: '".$line."'.");
		}
		
		//$lines = str_replace("\n", "\\n", $lines);
		$sections = DiffTools::SplitByStartingLine($lines, '/^@@ \-[0-9]+\,[0-9]+ \+[0-9]+\,[0-9]+ @@/');
		foreach($sections as $section) {
			$diffsection = new DiffSection($section);
			array_push($this->sections, $diffsection);
		}
	}
	
	public function GetFilename_From() {
		return $this->a_filename;
	}

	public function GetFilename_To() {
		return $this->b_filename;
	}
	
	public function isBinary() {
		return $this->isBinary;
	}
	
	public function IsLineChanged($lineno) {
		foreach ($this->sections as $section) {
			/* @var $section DiffSection */
			if ($section->IsLineChanged($lineno)) 
				return true; //preliminary exit allowed - no need to check other sections
		}
		return false;
	}
	
	public function GetDeletedLinesBefore($lineno) {
		foreach ($this->sections as $section) {
			/* @var $section DiffSection */
			$lines = $section->GetDeletedLinesBefore($lineno);
			if (sizeof($lines) > 0) {
				return $lines; //only one section can fit the same line!
			}
			//else - search next section
		}
		return array();
	}
	
	public function GetNewLineNumberForOldLineNumber($oldlinenumber) {
		$newlinenumber = $oldlinenumber;
		foreach ($this->sections as $section) {
			/* @var $section DiffSection */
			$newlinenumber = $section->GetNewLineNumberForOldLineNumber($newlinenumber);
		}
		return $newlinenumber;
	}
	public function GetOldLineNumberForNewLineNumber($linenumber) {
		$oldlinenumber = $linenumber;
		foreach ($this->sections as $section) {
			/* @var $section DiffSection */
			$oldlinenumber = $section->GetOldLineNumberForNewLineNumber($oldlinenumber);
		}
		return $oldlinenumber;
	}
	public function GetNextBestNewLineNumberForOldLineNumber($oldlinenumber) {
		$currentoldnumber = $oldlinenumber;
		$iteration_imiter = 50000;
		do {
			$newlinenumber = $currentoldnumber;
			foreach ($this->sections as $section) {
				/* @var $section DiffSection */
				$newlinenumber = $section->GetNewLineNumberForOldLineNumber($newlinenumber);
			}
			$currentoldnumber++;
			$iteration_imiter--;
		} while (($newlinenumber == false) && ($iteration_imiter > 0));
		
		if ($iteration_imiter <= 0) {
			throw new Exception("Aborting seach after 50000 cycles for the next existing line in file '".$this->b_filename."'. ".
				"Could not find new line number for old line '".$oldlinenumber."'."
			);
		}
		
		return $newlinenumber;
	}
	
	
}

?>