<?php
require_once 'config.php';

//-----------------------------------------------------------
// classes
//-----------------------------------------------------------
require_once 'classes/ReviewFileList.php';
require_once 'classes/Diff.php';
require_once 'classes/SFSectionTemplate.php';
require_once 'classes/FileView.php';

define("REVIEW_GIT_COMMAND", "git --git-dir=".$repository['location']."/.git --work-tree=".$repository['location']); 

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
// test environment first
//-----------------------------------------------------------
$gitoutput = shell_exec(REVIEW_GIT_COMMAND." 2>&1");
if ( substr(trim($gitoutput), 0, 10) != "usage: git" ) {
	echo "<html><body><h1>Setup Error</h1>".
		"The command 'git' was not found on your machine.<br>\n".
		"Please ensure the enviroment variables (e.g. PATH )are set correctly.<br>\n".
		"<br>\n".
		"To test the correct setup type 'git' on your console.<br>\n".
		"(Restarting your webserver might be necessary after changing the PATH parameter.)<br>\n".
		"<br><b>Details:</b><pre>".$gitoutput."</pre>";
		"</body></html>";
	exit();
}
$gitoutput = null; //collect garbadge


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
$defaultcommit = true; //systems selects a commit on own merits
$usersince = "";
if (sizeof($commits) >= 3) {
	$usersince = $commits[2]['commit']; //diff last commit
}
if (isset($_GET['since'])) {
	$usersince = $_GET['since'];
	$defaultcommit = false;
}

//-----------------------------------------------------------
// load review files
//-----------------------------------------------------------
$reviewfileslocation = $repository['location'].$repository['reviewspath'];
$reviews = new ReviewFileList($reviewfileslocation, $defaultcommit ? "" : $usersince);


//-----------------------------------------------------------------------------
//select better usersince if user did not override and if review file exists
//-----------------------------------------------------------------------------
if ($defaultcommit) {
	
	$latestreviewfile = $reviews->getLastestReviewFile();
	if ($latestreviewfile != null) {
		$usersince = $latestreviewfile->since;
		//need reload reviews!
		//TODO: LOW: do not reload but just call adjustments
		$reviews = new ReviewFileList($reviewfileslocation, $usersince);
	}
}

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