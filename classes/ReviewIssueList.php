<?php

class ReviewIssueList {

	private $issues = array();
	
	public function addIssue( ReviewIssue $issue) {
		array_push($this->issues, $issue);
	}
	
	public function getAsArray() {
		return $this->issues;
	}
	
	public function getWorstState() {
		$worst = "";
		$worstpos = -1;
		foreach($this->issues as $issue) {
			/* @var $issue ReviewIssue */
			$issue_status = $issue->getStatus(true);
			$pos = array_search($issue_status, ReviewIssue::$DIRTYSTATES);
			if (($pos !== false) && ($pos > $worstpos)) {
				$worstpos = $pos;
				$worst = $issue_status;
			}
		}
		return $worst;
	}
	
	public function isEmpty() {
		return sizeof($this->issues) == 0;
	}
	
}

?>