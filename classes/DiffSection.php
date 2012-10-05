<?php

class DiffSection {
	private $lines = array();
	private $remove_from = -1;
	private $remove_to = -1;
	private $remove_length = 0;
	private $insert_from = -1;
	private $insert_to = -1;
	private $insert_length = 0;	
	private $linenumbers = array();
	private $removedbeforeline = array();
	private $oldlineisnow = array(); //translates old line numbers to new line numbers
	private $newlinewasbefore = array(); //translates new line numbers to old line numbers
	
	public function __construct($lines) {
		
		$matches = array();
		preg_match('/^@@ \-([0-9]+)\,([0-9]+) \+([0-9]+)\,([0-9]+) @@/', $lines, $matches);
		$lines = explode("\n", $lines, 2);
		$lines = $lines[1];
		
		$this->remove_from = $matches[1];
		$this->remove_length   = $matches[2];
		$this->remove_to = $this->remove_from + $this->remove_length -1;
		
		$this->insert_from = $matches[3];
		$this->insert_length   = $matches[4];
		$this->insert_to = $this->insert_from + $this->insert_length -1;
		
		
		$this->lines = explode("\n", $lines);
		$addcount = 0;
		$delcount = 0;
		$keepcount = 0;
		$othercount = 0;
		$currentline = $this->insert_from;
		$currentremovedline = $this->insert_from;
		
		$oldline = $this->remove_from;
		$newline = $this->insert_from;
		
		foreach ($this->lines as $line) {
			if ($line == "") {
				$othercount++;
				if ($addcount + $delcount + $keepcount == 0)
					$this->emptylines_upfront++;  
				continue;
			}
			
			switch (substr($line, 0, 1)) {
				case "+": 
					$addcount++;
					$this->linenumbers[$currentline] = "added";
					$this->newlinewasbefore[$currentline] = false;
					$newline++;
					$currentline++;
					break;
					
				case "-": 
					$delcount++;
					if (!isset($this->removedbeforeline[$currentline])) 
						$this->removedbeforeline[$currentline] = array();
					array_push($this->removedbeforeline[$currentline], substr($line, 1));
					$this->oldlineisnow[$oldline] = false;
					$oldline++;
					break;
					
				case " ":
					$keepcount++;
					$this->linenumbers[$currentline] = "keep";
					$this->oldlineisnow[$oldline] = $newline;
					$this->newlinewasbefore[$currentline] = $oldline;
					$oldline++;
					$newline++;
					$currentline++;
					break;
					
				case "\\": 
					$othercount++;
					break;
					
				default:
					throw new Exception("Found character '".substr($line, 0, 1).
						"'(".ord(substr($line, 0, 1)).") at the start of a diff section line. ".
						"Expected +,-, space or \\."
					);
			}
		}
		
		if (($this->remove_to - $this->remove_from + 1) != ($delcount + $keepcount))
			throw new Exception("Unexpected diff. The removed lines (".($delcount + $keepcount).")".
				" are not compatible to the given span (".($this->remove_to - $this->remove_from + 1).").");
			
		if (($this->insert_to - $this->insert_from + 1) != ($addcount + $keepcount))
			throw new Exception("Unexpected diff. The inserted lines (".($addcount + $keepcount).")".
				" are not compatible to the given span (".($this->insert_to - $this->insert_from + 1).").");
		
	}
	
	public function IsLineChanged($lineno) {
		return isset($this->linenumbers[$lineno]) && ($this->linenumbers[$lineno] == "added");
	}
	
	public function GetDeletedLinesBefore($lineno) {
		if (isset($this->removedbeforeline[$lineno])) {
			return $this->removedbeforeline[$lineno];
		} else {
			return array();
		}
	}
	
	public function GetNewLineNumberForOldLineNumber($oldlinenumber) {
		if (isset($this->oldlineisnow[$oldlinenumber])) {
			return $this->oldlineisnow[$oldlinenumber];
		} else {
			if ($oldlinenumber < $this->remove_from) 
				return $oldlinenumber;

			if ($oldlinenumber > $this->remove_to) 
				return $oldlinenumber + $this->insert_length - $this->remove_length;
				
			
			throw new Exception("Adjusting old line number to new one failed. ".
				"Please fix the code in DiffSection::GetNewLineNumberForOldLineNumber()."
			);
		}
	}
	
	public function GetOldLineNumberForNewLineNumber($linenumber) {
		if (isset($this->newlinewasbefore[$linenumber])) {
			return $this->newlinewasbefore[$linenumber];
		} else {
			if ($linenumber < $this->insert_from) 
				return $linenumber;

			if ($linenumber > $this->insert_to) 
				return $linenumber - $this->insert_length + $this->remove_length;
				
			throw new Exception("Adjusting new line number to old one failed. ".
				"Please fix the code in DiffSection::GetOldLineNumberForNewLineNumber()."
			);
		}
	}
	
}

?>