<?php

class DiffTools {
	
	public static function SplitByStartingLine($content, $startpattern) {
		$inblock = false;
		$resultlist = array();
		$index = -1; 
		foreach(explode("\n", $content) as $line) {
			if (preg_match($startpattern, $line)) {
				$index++;
				$resultlist[$index] = "";
				$inblock = true;
			}
			if ($inblock) {
				if (empty($resultlist[$index])) {
					$resultlist[$index] .= $line;
				} else {
					$resultlist[$index] .= "\n".$line;
				}
			}
		}

		return $resultlist;
	}
}

?>