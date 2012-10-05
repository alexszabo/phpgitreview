<?php
require_once 'ReviewIssue.php';

class ReviewFile {
	public $reviewfilename = "";
	public $since = "";
	public $until = "";
	public $data = array(); //format [filename][0..n] => each (0..n) is a ReviewIssue instance
	
	public function __construct($reviewfilename, $usersince) {
		$this->reviewfilename = $reviewfilename;
		$contents = file_get_contents($this->reviewfilename);

		$currentfile = "";
		$currentissue = new ReviewIssue(); //dummy to catch initial lines without file relation
		$currentcomment = null; /* @var $currentcomment ReviewIssueComment */
		$currentsince = "";
		
		$linecount = 0;
		foreach(explode("\n", $contents) as $line) {
			$linecount++;

			// ---------- since: ----------
			$matches = array();
			if (preg_match('/^\s*since(\s*\:)?\s+(.*)$/', $line, $matches)) {
				if ($this->since != "")
					throw new Exception("Must not use 'since' command twice in file '".$this->reviewfilename."'.");
				$this->since = trim($matches[2]);
				continue;
			}
			
			// ---------- until: ----------
			$matches = array();
			if (preg_match('/^\s*until(\s*\:)?\s+(.*)$/', $line, $matches)) {
				if ($this->until != "")
					throw new Exception("Must not use 'until' command twice in file '".$this->reviewfilename."'.");
				$this->until = trim($matches[2]);
				continue;
			}
			
			// ---------- file: ----------
			$matches = array();
			if (preg_match('/^\s*file(\s*\:)?\s+(.*)$/', $line, $matches)) {
				$currentfile = trim($matches[2]);
				if (!isset($this->data[$currentfile]))
					$this->data[$currentfile] = array();
				$currentissue = new ReviewIssue();
				array_push($this->data[$currentfile], $currentissue);
				$currentissue->sourcefile = $currentfile;
				continue;
			}
			
			// ---------- lines: ----------
			$matches = array();
			if (preg_match('/^\s*lines?(\s*\:)?\s+(.*)$/', $line, $matches)) {
				
				//if already occupied - create new issue entry
				if (($currentissue->areLinesSet()) || ($currentissue->isSomeContentDefined())) {
					$currentissue = new ReviewIssue();
					array_push($this->data[$currentfile], $currentissue);
					$currentissue->sourcefile = $currentfile;
				}
				
				$currentissue->setLines( $matches[2] );
				continue;
			}
			
			// ---------- old lines: ----------
			$matches = array();
			if (preg_match('/^\s*old\s*lines?(\s*\:)?\s+(.*)$/', $line, $matches)) {
				
				//if already occupied - create new issue entry
				if (($currentissue->areOldLinesSet()) || ($currentissue->isSomeContentDefined())) {
					$currentissue = new ReviewIssue();
					array_push($this->data[$currentfile], $currentissue);
					$currentissue->sourcefile = $currentfile;
				}
				
				$currentissue->setOldLines( $matches[2] );
				continue;
			}
			
			// ---------- status: ----------
			$matches = array();
			if (preg_match('/^\s*status(\s*\:)?\s+(.*)$/', $line, $matches)) {
				$currentissue->setStatus( strtolower(trim($matches[2])) );
				if (!in_array($currentissue->getStatus(), ReviewIssue::$STATES)) {
					throw new Exception("The status '".$currentissue->status."' is not valid".
						" (file ".$reviewfilename."). ".
						"Allowed status are: ".implode(", ", ReviewIssue::$STATES)."."
					);
				}
				continue;
			}

			// ---------- comment by someone: ----------
			$matches = array();
			if (preg_match('/^\s*comment\s+by\s+([\w\s]+)\s*\:\s+(.*)$/', $line, $matches)) {
				//$currentissue->status = $matches[2];
				if ($currentcomment != null) 
					$currentissue->addComment($currentcomment);
				$currentcomment = new ReviewIssueComment();
				$currentcomment->author = $matches[1];
				$currentcomment->comment = $matches[2];
				continue;
			}
			
			//if in comment .. append it ...
			if ($currentcomment != null) {
				if (trim($line) == "") {
					//close comment
					$currentissue->addComment($currentcomment);
					$currentcomment = null;
				} else {
					//add to existing comment
					$currentcomment->comment .= "\n".$line;
				}
				continue;
			}
			
			if (preg_match('/^\/\/(.*)$/', $line)) {
				//ignore comment lines
				continue;
			}
			
			if (trim($line) == "") {
				//ignore empty lines
				continue;
			}
			
			throw new Exception("Found unrecognizable line in review file '".$reviewfilename."' at line ".$linecount.": ".$line);
			
			
		}
		if ($currentcomment != null) {
			//close comment
			$currentissue->addComment($currentcomment);
			$currentcomment = null;
		}
		
		//adjust issue lines by Diff between until and working copy
		$this->AdjustIssueLinesByDiff($usersince);
		
	}
	
	/**
	 * Adjusts the iusse line numbers by the diff between the 
	 * commit (stated by until) and the working copy.
	 */
	private function AdjustIssueLinesByDiff($usersince) {
		
		$difflines_start_to_since = shell_exec(REVIEW_GIT_COMMAND." diff ".$usersince." ".$this->since." 2>&1");
		$diff_start_to_since = new Diff($difflines_start_to_since);

		$difflines_start_to_until = shell_exec(REVIEW_GIT_COMMAND." diff ".$usersince." ".$this->until." 2>&1");
		$diff_start_to_until = new Diff($difflines_start_to_until);

		$difflines_until_to_end = shell_exec(REVIEW_GIT_COMMAND." diff ".$this->until." 2>&1");
		$diff_until_to_end = new Diff($difflines_until_to_end);
		
		foreach ($this->data as $filename => $issuelist) {
			foreach($issuelist as $issue) {
				/* @var $issue ReviewIssue */
				$difffile_start_to_since = $diff_start_to_since->GetDiffFile($issue->sourcefile);
				$difffile_start_to_until = $diff_start_to_until->GetDiffFile($issue->sourcefile);
				$difffile_until_to_end = $diff_until_to_end->GetDiffFile($issue->sourcefile);
				
				$issue->adjustByDiffs($difffile_start_to_since, $difffile_start_to_until, $difffile_until_to_end);
			}
		}		
	}
	
	public function AddIssue(ReviewIssue $issue) {
		throw new Exception("never use AddIssue");
		array_push($this->issues, $issue);
	}
	
	public function GetIssues() {
		throw new Exception("never use GddIssue");
		return $this->issues;
	}
	
	/**
	 * @return ReviewIssueList
	 */
	public function getReviewIssueForLine($filename, $oldlineno, $lineno) {
		$list = new ReviewIssueList();
		if (isset($this->data[$filename])) {
			foreach ($this->data[$filename] as $issue) {
				/* @var $issue ReviewIssue */
				if ($issue->isReviewIssueForLine($filename, $oldlineno, $lineno)) {
					$list->addIssue($issue);
				}
			}
		}
		return $list;
	}
	
	public function getOrphanedReviewIssueBeforeLine($filename, $lineno) {
		$list = new ReviewIssueList();
		if (isset($this->data[$filename])) {
			foreach ($this->data[$filename] as $issue) {
				/* @var $issue ReviewIssue */
				if ($issue->isOrphanedReviewIssueForLine($filename, $lineno)) {
					$list->addIssue($issue);
				}
			}
		}
		return $list;
	}
	
}

?>