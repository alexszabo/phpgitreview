<?php
//define("DEBUG", true);

ini_set("memory_limit","50M");

/* This path should be adjusted to your machine and system: */
define("REVIEW_GIT_PATH", "C:\\\"Program Files (x86)\"\\Git\\bin\\git.exe");

/* The path separator should fit your system. "\\" is for windows machines */
$repository = array(
	'location'    => "..\\myproject",
	'reviewspath' => "\\reviews",
);

?>