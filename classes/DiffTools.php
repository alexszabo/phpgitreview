<?php

class DiffTools {
	
	public static function SplitByStartingLine($content, $startpattern) {
		$inblock = false;
		$resultlist = array();
		$currentlines = array();
		foreach(explode("\n", $content) as $line) {
			if (preg_match($startpattern, $line)) {
				if ($inblock) {
					array_push($resultlist, implode("\n", $currentlines));
					$currentlines = array();
				}
				$inblock = true;
			}
			if ($inblock) {
				array_push($currentlines, $line);
			}
		}
		if ($inblock) {
			array_push($resultlist, implode("\n", $currentlines));
		}
		
		return $resultlist;
	}
}

?>