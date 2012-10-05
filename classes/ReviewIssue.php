<?php
require_once 'ReviewIssueComment.php';

class ReviewIssue {
	
	public static $STATES = array( "ok", "info", "warn", "error" );
	public static $DIRTYSTATES = array( "ok", "ok_dirty", "info", "info_dirty", "warn", "warn_dirty", "error", "error_dirty" );
	
	public $sourcefile = "";
	private $status    = "";
	public $_comments_already_shown = false;
	
	private $lines_array    = array();
	private $oldlines_array = array();
	private $orphaned_lines = array();
	
	private $comments = array(); // array of ReviewIssueComment instances
	
	private $isDirty = false; //true, if any line was removed
	
	
	public function getLinesArray($endoffirstblockonly=false) {
		if ($endoffirstblockonly) {
			return $this->getLineWithCommentsForLines($this->lines_array);
		} else {
			return $this->lines_array; 
		}
	}
	
	public function getOldLinesArray($endoffirstblockonly=false) {
		if ($endoffirstblockonly) {
			return $this->getLineWithCommentsForLines($this->oldlines_array);
		} else {
			return $this->oldlines_array;
		}
	}
	
	public function setLines($lines) {
		$this->lines_array = ReviewIssue::getLinesFromStringDefinition($lines);
	}
	
	public function setOldLines($oldlines) {
		$this->oldlines_array = ReviewIssue::getLinesFromStringDefinition($oldlines);
	}
	
	public function areLinesSet() {
		return ($this->lines != "");
	}
	
	public function areOldLinesSet() {
		return ($this->oldlines != "");
	}
	
	public function isSomeContentDefined() {
		if ($this->status != "")         return true;
		if (sizeof($this->comments) > 0) return true;
		if ($resolved)                   return true;
		
		return false;
	}
	
	public function addComment(ReviewIssueComment $comment) {
		array_push($this->comments, $comment);
	}
	
	public function getComments() {
		return $this->comments;
	}
	
	public function setStatus($status) {
		$this->status = $status;
	}
	
	public function getStatus($adddirty=false) {
		if ($adddirty) {
			return $this->status.($this->isDirty ? '_dirty' : '');
		} else {
			return $this->status;
		}
	}
	
	
	public function isReviewIssueForLine($filename, $oldlineno, $lineno, $endoffirstblockonly=false) {
		
		if ($filename != $this->sourcefile) return false;
		
		if ($endoffirstblockonly) {
			$lines = $this->getLineWithCommentsForLines($this->lines_array);
			if (isset($lines[ intval($lineno) ])) return true;
			
			$oldlines = $this->getLineWithCommentsForLines($this->oldlines_array);
			if (isset($oldlines[ intval($oldlineno) ])) return true;
		} else {
			if (isset($this->lines_array[    intval($lineno)    ])) return true;
			if (isset($this->oldlines_array[ intval($oldlineno) ])) return true;
		}
		
		return false;
	}
	
	public function isOrphanedReviewIssueForLine($filename, $lineno) {
		if ($filename != $this->sourcefile) return false;
		
		if (isset($this->orphaned_lines[ intval($lineno) ])) return true;
		
		return false;
	}
	
	
	private static function getLinesFromStringDefinition($definition, $endoffirstblockonly=false) {
		$lines = array();
		$parts = explode(",", $definition);
		foreach($parts as $part) {
			$part = trim($part);
			$matches = array();
			if (preg_match('/([0-9]+)\s*\-\s*([0-9]+)/', $part, $matches)) {
				if ((intval($matches[1]) <= intval($matches[2]))) {
					for($i = intval($matches[1]); $i <= intval($matches[2]); $i++) {
						$lines[$i] = true;
					}
				}
			} else {
				$lines[intval($part)] = true;
			}
			
			if ($endoffirstblockonly) //early out 
				return $lines;;
		}
		return $lines;
	}
	
	/**
	 * @param DiffFile $start_to_since or null
	 * @param DiffFile $start_to_until or null
	 * @param DiffFile $until_to_end or null
	 */
	public function adjustByDiffs( $start_to_since, $start_to_until, $until_to_end ) {
		$linesarray = array();
		$oldlinesarray = array();
		$orphanedlinesarray = array();
		foreach ($this->lines_array as $lineno => $value) {
			if ($until_to_end != null) {
				$newlineno = $until_to_end->GetNewLineNumberForOldLineNumber($lineno);

				if ($newlineno == false) {
					//the line does no longer exist in the current version
					$this->isDirty = true;
					if ($start_to_until != null) {
						$oldlineno = $start_to_until->GetOldLineNumberForNewLineNumber($lineno);
						if ($oldlineno == false) {
							//the line did not exist in the version selected by usersince
							//TODO: MEDIUM: did neither exist before - nor in the end - show as intermediate line later
							$oldlineno = $until_to_end->GetNextBestNewLineNumberForOldLineNumber($lineno);
							$orphanedlinesarray[$oldlineno] = true;
						} else {
							$oldlinesarray[$oldlineno] = true;
						}
					} else {
						$oldlinesarray[$lineno] = true;
					}
				} else {
					$linesarray[$newlineno] = true;
				}
			} else {
				$linesarray[$lineno] = true;
			}
		}
		
		foreach ($this->oldlines_array as $oldlineno => $value) {
			if ($start_to_since != null) {
				$olderlineno = $start_to_since->GetOldLineNumberForNewLineNumber($olderlineno);
				if ($olderlineno == false) {
					//TODO: MEDIUM: is old and had no line before - show as intermediate line later
				} else {
					$oldlinesarray[$olderlineno] = true;
				}
			} else {
				$oldlinesarray[$oldlineno] = true;
			}
		}
		
		$this->lines_array = $linesarray;
		$this->oldlines_array = $oldlinesarray;
		$this->orphaned_lines = $orphanedlinesarray;
		
	}
	
	public function adjustbyPrecedingDiff(DiffFile $difffile) {
		//TODO: HIGH: implement adjustbyPrecedingDiff
		throw new Exception('adjustbyPrecedingDiff() is not implemented');
	}
	
	private function getLineWithCommentsForLines($lines) {
		if (sizeof($lines) == 0) return array();
		ksort($lines);
		$first = ""; 
		foreach ($lines as $lineno => $val) {
			if ($first == "") {
				$first = $lineno; //TODO: HIGH: improve this first line approach
			}
		}
		return array( $first => true); 
	}
}

?>