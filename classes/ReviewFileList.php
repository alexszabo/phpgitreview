<?php
require_once 'ReviewFile.php';
require_once 'ReviewIssueList.php';

class ReviewFileList {
	
	private $reviewfiles = array();

	public function __construct($review_files_path, $usersince="") {
		$reviewfiles = array();
		if (is_dir($review_files_path)) {
		    if ($dh = opendir($review_files_path)) {
		        while (($file = readdir($dh)) !== false) {
		        	if (preg_match('/^.*(\.review)$/i', $file)) {
		        		$reviewfiles[$file] = new ReviewFile($review_files_path.DIRECTORY_SEPARATOR.$file, $usersince);
		        	}
		        }
		        closedir($dh);
		    }
		}
		ksort($reviewfiles);
		$this->reviewfiles = $reviewfiles;		
	}
	
	/**
	 * @return ReviewIssueList
	 */
	public function getReviewIssueForLine($filename, $oldlineno, $lineno) {
		$list = new ReviewIssueList();
		
		foreach ($this->reviewfiles as $reviewfile) {
			/* @var $reviewfile ReviewFile */
			$rf_list = $reviewfile->getReviewIssueForLine($filename, $oldlineno, $lineno);
			foreach($rf_list->getAsArray() as $rf_issue) {
				$list->addIssue($rf_issue);
			}
		}
		
		return $list; 
	}
	
	/**
	 * @return ReviewIssueList
	 */
	public function getOrphanedReviewIssueBeforeLine($filename, $lineno) {
		$list = new ReviewIssueList();
		
		foreach ($this->reviewfiles as $reviewfile) {
			/* @var $reviewfile ReviewFile */
			$rf_list = $reviewfile->getOrphanedReviewIssueBeforeLine($filename, $lineno);
			foreach($rf_list->getAsArray() as $rf_issue) {
				$list->addIssue($rf_issue);
			}
		}
		
		return $list; 
	}	

	/**
	 * @return ReviewFile or null
	 */
	public function getLastestReviewFile() {
		if (sizeof($this->reviewfiles) == 0) 
			return null;
			
		$keys = array_keys($this->reviewfiles);
		$last_key = $keys[ sizeof($keys)-1 ];
		$last_rfile = $this->reviewfiles[$last_key];
		return $last_rfile;
	}
	
}

?>