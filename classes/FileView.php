<?php
require_once 'markdown.php';

class FileView {
	
	private $contents;
	private $diffile = null;
	private $filename; 
	private $reviews;

	public function __construct($filename, $contents, DiffFile $diffile, ReviewFileList $reviews) {
		$this->contents = $contents;
		$this->diffile  = &$diffile;
		$this->filename = $filename;
		$this->reviews  = &$reviews;
	}
	
	public function ParseFileView(SFSectionTemplate $t) {
		$t->SetVar('filename', $this->filename);
		$diffile = &$this->diffile; /* @var $diffile DiffFile */
		$reviews = $this->reviews; /* @var $reviews ReviewFileList */
		
		if ($this->diffile->isBinary()) {
			$t->ParseSection('binary');
			$t->ParseSection('fileblock');
			return;
		}
		
		$lineno = 0;
		$oldlineno = 0;
		foreach(explode("\n", $this->contents) as $line) {
			$lineno++;
			$oldlineno++;
			
			
			$deletedlines = $diffile->GetDeletedLinesBefore($lineno);
			foreach($deletedlines as $delline) {
				$t->SetVar('codeline_class', 'deleted');
				$t->SetVar('oldlineno', $oldlineno);
				$t->SetVar('lineno', '');
				$delline = str_replace("\t", "    ", $delline);
				if (strlen($delline) > 80) {
					$t->SetVar('code', htmlspecialchars(substr($delline,0,80)));
					$t->SetVar('codeover80', htmlspecialchars(substr($delline,80)));
					$t->ParseSection('codepartover80');
				} else {
					$t->SetVar('code', htmlspecialchars($delline));
				}	

				$issues = $reviews->getReviewIssueForLine($this->filename, $oldlineno, $lineno);
				/* @var $issues ReviewIssueList */
				
				$this->ParseComments($t, $issues, $oldlineno, $lineno);
				
				$worststate = $issues->getWorstState();
				if ($worststate != '') {
					$t->SetVar('codeline_class', 'deleted '.$worststate);
				}
				
				$t->ParseSection('line');
				$oldlineno++;
			}
			
			
			$isadded = $diffile->IsLineChanged($lineno);
			
			if ($isadded) $oldlineno--;
			
			//parse out issues with comments that have neither in old file nor in new file a line
			$orphaned_issues = $reviews->getOrphanedReviewIssueBeforeLine($this->filename, $lineno);
			if (!$orphaned_issues->isEmpty()) {
				$t->SetVar('lineno', '');
				$t->SetVar('oldlineno', '');
				$t->SetVar('code', '');
				
				$this->ParseComments($t, $orphaned_issues, '', '');
				
				$t->SetVar('codeline_class', 'notexisting');
				$worststate = $orphaned_issues->getWorstState();
				if ($worststate != '') {
					$t->SetVar('codeline_class', 'notexisting '.$worststate);
				}
				
				$t->ParseSection('line');				
			}
			
			
			
			$t->SetVar('lineno', $lineno);
			$t->SetVar('oldlineno', $isadded ? '' : $oldlineno);
			
			$line = str_replace("\t", "    ", $line);
			
			if (strlen($line) > 80) {
				$t->SetVar('code', htmlspecialchars(substr($line,0,80)));
				$t->SetVar('codeover80', htmlspecialchars(substr($line,80)));
				$t->ParseSection('codepartover80');
			} else {
					$t->SetVar('code', htmlspecialchars($line));
			}

			$issues = $reviews->getReviewIssueForLine($this->filename, $isadded ? "" : $oldlineno, $lineno);
			/* @var $issues ReviewIssueList */
			
			$this->ParseComments($t, $issues, $oldlineno, $lineno);
			
			$t->SetVar('codeline_class', $isadded ? 'added' : '');
			$worststate = $issues->getWorstState();
			if ($worststate != '') {
				$t->SetVar('codeline_class', $isadded ? 'added '.$worststate : $worststate);
			}
			
			$t->ParseSection('line');
		}
		$t->ParseSection('codeblock');	
		$t->ParseSection('fileblock');
	}
	
	/**
	 * Parses the comments if they apply to this line.
	 * If $oldlineno and $lineno are both empty strings, the comments
	 * will be parsed out in any case.
	 *
	 * @param SFSectionTemplate $t the template to parse to
	 * @param ReviewIssueList $issues issues where the comments come from
	 * @param int $oldlineno or empty string
	 * @param int $lineno or empty string
	 */
	private function ParseComments(SFSectionTemplate $t, ReviewIssueList $issues, $oldlineno, $lineno) {
		$md_parser = new Markdown_Parser();
		//$md_parser->no_entities = true;
		//$md_parser->no_markup = true;

		$parsecomments_wrap = false;
		foreach ($issues->getAsArray() as $issue) {
			/* @var $issue ReviewIssue */
			if (($issue->isReviewIssueForLine($this->filename, $oldlineno, $lineno, true)) || (($oldlineno == '') && ($lineno == ''))) {
				if (!$issue->_comments_already_shown) {
					foreach($issue->getComments() as $comment) {
						/* @var $comment ReviewIssueComment */
						$t->SetVar('commenter', $comment->author);
						$html = $md_parser->transform($comment->comment);
						$t->SetVar('commenttext', $html);
						$t->ParseSection('comment');
						$parsecomments_wrap = true;
					}
					$issue->_comments_already_shown = true;
				}
			}
			if ($parsecomments_wrap) {
				$t->SetVar('lines', implode(",", $issue->getLinesArray()));
				$t->SetVar('oldlines', implode(",", $issue->getOldLinesArray()));
				$t->SetVar('comments_status', strtolower(trim($issue->getStatus())));
				$t->ParseSection('comments');
				$parsecomments_wrap = false;
			}
		}
	}
	
}

?>