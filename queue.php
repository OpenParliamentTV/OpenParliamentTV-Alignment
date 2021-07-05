<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
set_time_limit(0);
date_default_timezone_set('CET');
ob_implicit_flush(true);
ob_end_flush();

include_once("config.php");
include_once("alignment.php");

processQueue();

function processQueue() {

	global $conf;

	logMessage("Processing queue ...\n");

	$queueFiles = array_values(array_diff(scandir($conf["dir"]["input"]), array('.', '..', '.DS_Store', '.gitkeep', '.gitignore')));

	$error = false;

	foreach($queueFiles as $file) {

		logMessage("\nProcessing ".$file."\n");

		try {
			forceAlignXMLData($file,$conf["sleep"]);
		} catch (Exception $e) {
			logMessage("Error processing file:\n".$e->getFile()."\n"."Line: ".$e->getFile()."\n".$e->getMessage()."\n");

			$error = true;
		} 
		
		//unlink($conf["dir"]["input"]."/".$file);

	}

	if ($error) {
		logMessage("Error: Indexing queue could not be completed\n");
	} else {
		logMessage("\nSuccess: finished processing queue\n\n");
	}
}

function logMessage($message) {
	/*
	echo $message;
	echo "</br>";
	*/
	file_put_contents("alignment-log.txt", $message, FILE_APPEND);
}

?>