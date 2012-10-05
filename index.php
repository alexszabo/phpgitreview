<?php
require_once 'config.php';

//-----------------------------------------------------------
// classes
//-----------------------------------------------------------
require_once 'classes/ReviewFileList.php';
require_once 'classes/Diff.php';
require_once 'classes/SFSectionTemplate.php';
require_once 'classes/FileView.php';

define("REVIEW_GIT_COMMAND", "cd ".$repository['location']."&".REVIEW_GIT_PATH);

//-----------------------------------------------------------
// DEBUG
//-----------------------------------------------------------
//TODO: LOW: remove the dump() function - only needed for debugging 
function dump($values, $die=true) {
	echo "<pre>";
	print_r($values);
	echo "</pre>";
	if ($die) 
		die("<pre>------------- END OF DUMP ------------------</pre>");
}


//-----------------------------------------------------------
// variables
//-----------------------------------------------------------
$commits = array(
	0 => array( //current working directoty has the index 0
		"commit" => '', //working copy - no commit
	),
	// the HEAD has the index 1
	// all commits before have a ascending index according to log position
	//TODO: HIGH: clarify how to handle merges 
);
 

//-----------------------------------------------------------
// load commits
//-----------------------------------------------------------
$commit_index = 0;
$commit = null;
$log = shell_exec(REVIEW_GIT_COMMAND." log 2>&1");

$parts = DiffTools::SplitByStartingLine($log, '/^commit\s(.*)$/');
foreach($parts as $part) {
	$commit = array();
	$commit['committext'] = ''; 
	 
	foreach(explode("\n", $part) as $line) {
		if (substr($line, 0, 7) == "commit ") {
			$commit['commit'] = substr($line, 7);
		} else
		if (substr($line, 0, 8) == "Author: ") {
			$commit['author'] = substr($line, 8);
		} else
		if (substr($line, 0, 8) == "Date:   ") {
			$commit['date'] = substr($line, 8);
		} else {
			if (trim($line) != '') {
				if ($commit['committext'] != '')
					$commit['committext'] .= "\n";
				$commit['committext'] .= trim($line);
			}
		}
	}
	$commit_index++;
	$commits[$commit_index] = $commit; 
}




$t = new SFSectionTemplate('template.html');

//-----------------------
//select since commit
//-----------------------
$usersince = "";
if (isset($_GET['since'])) {
	$usersince = $_GET['since'];
}
if ($usersince == "") {
	//TODO: MEDIUM: lost the possibility for getting since before loading reviews - fix that later
//	$reviewfile = $reviews->getLastestReviewFile();
//	if ($reviewfile != null) {
//		$usersince = $reviewfile->since;
//	} else {
		$usersince  = $commits[sizeof($commits)-1]['commit'];
//	}
}
$lastcommit = $commits[sizeof($commits)-1]['commit'];

//-----------------------------------------------------------
// load review files
//-----------------------------------------------------------
$reviewfileslocation = $repository['location'].$repository['reviewspath'];
$reviews = new ReviewFileList($reviewfileslocation, $usersince != "" ? $usersince : $lastcommit);


//-----------------------
//show commits
//-----------------------
$found_selected_commit = false;
foreach ($commits as $commit) {
	if ($commit['commit'] == '') continue;

	$t->SetVar('commitselected', '');
	if ($usersince == $commit['commit']) {
		$t->SetVar('commitselected', 'selected');
		$found_selected_commit = true;
	}
	if (!$found_selected_commit) {
		$t->SetVar('commitselected', 'selected');
	}
	
	$t->SetVar('committext', $commit['committext']);	
	$t->SetVar('commitid',   $commit['commit']);
	$t->SetVar('author',     $commit['author']);
	$t->ParseSection('commit');
}

$username = $difflines = shell_exec(REVIEW_GIT_COMMAND." config user.name 2>&1");


//$difflines = shell_exec(REVIEW_GIT_COMMAND." diff ".$since." ".$commits[1]['commit']." 2>&1");
$difflines = shell_exec(REVIEW_GIT_COMMAND." diff ".$usersince." 2>&1");
$diff = new Diff($difflines);

foreach($diff->GetFileNames() as $filename) {
	
	//skip *.review files
	if (preg_match('/^(.*)\.review$/', $filename)) continue;
	
	$contents = @file_get_contents($repository['location'].DIRECTORY_SEPARATOR.$filename);
	//$contents = shell_exec(REVIEW_GIT_COMMAND." show ".$commits[1]['commit'].":".$filename." 2>&1");
	if ($contents == "") continue;
	
	$difffile = $diff->GetDiffFile($filename);
	
	$fv = new FileView($filename, $contents, $difffile, $reviews);
	$fv->ParseFileView($t);
}


echo $t->Get();
exit();




?>