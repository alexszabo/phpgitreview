<?php
require_once 'DiffFile.php';
require_once 'DiffTools.php';

class Diff {
	private $diff_files = array();
	
	public function __construct($lines) {
		
		$fileblocks = DiffTools::SplitByStartingLine($lines, '#^diff \-\-git a/(.*) b/(.*)$#');
		foreach($fileblocks as $textblock) {
			array_push($this->diff_files, new DiffFile($textblock));
		}
	}
	
	public function GetFileNames() {
		$filenames = array();
		foreach ($this->diff_files as $difffile) {
			/* @var $difffile DiffFile */ 
			array_push($filenames, $difffile->GetFilename_To());
		}
		return $filenames;
	}
	
	/**
	 * @return DiffFile
	 */
	public function GetDiffFile($filename) {
		foreach ($this->diff_files as $difffile) {
			/* @var $difffile DiffFile */ 
			if ($filename == $difffile->GetFilename_To())
				return $difffile;
		}
		
		return null;
	}
	
}

?>