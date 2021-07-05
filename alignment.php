<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

set_time_limit(0);
ini_set('memory_limit', '500M');
date_default_timezone_set('CET');
ob_implicit_flush(true);
ob_end_flush();
//disable_ob();


/**
 * @param $XMLFilePath
 * @param $sleep 
 * @return mixed
 */
function forceAlignXMLData($XMLFilePath, $sleep=1) {

	global $conf;

	$fileName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $XMLFilePath);

	$file_content = file_get_contents($conf["dir"]["input"]."/".$XMLFilePath);

	ini_set('mbstring.substitute_character', 32);
	if (mb_detect_encoding($file_content) == 'ISO-8859-1') {
		$file_content = mb_convert_encoding($file_content, 'UTF-8', 'ISO-8859-1');
	}

	$file_content_xml = new SimpleXMLElement($file_content);

	$metaElem = $file_content_xml->body->div;
	
	$mediaFilePath = $metaElem["data-media-file-uri"];
	$mediaFilePathArray = preg_split("/\\//", $mediaFilePath);
	$mediaFileName = array_pop($mediaFilePathArray);

	if (!file_exists($conf["dir"]["cache"].'/'.$mediaFileName)) {
		logMessage("Audio file not found. Downloading file...\n");
		getMediaFile($mediaFilePath);
	} else {
		//logMessage("Audio file found (".$conf["dir"]["cache"]."/".$mediaFileName."). No download necessary.\n");
	}

	if (!file_exists($conf["dir"]["output"]."/".$fileName.".json")) {
		logMessage("Force aligning ".$mediaFileName." with ".$XMLFilePath." ...\n");
		
		sleep($sleep);
		forceAlignMedia($conf["dir"]["cache"]."/".$mediaFileName, $conf["dir"]["input"]."/".$XMLFilePath, $conf["dir"]["output"]."/".$fileName.".json");

	} else {
		
		logMessage("JSON timings file found (".$conf["dir"]["output"]."/".$fileName.".json). Force Align not necessary.\n");
		

	}

	sleep($sleep);

}

/**
 * @param $mediaFilePath
 * @return mixed
 */
function getMediaFile($mediaFilePath) {

	global $conf;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $mediaFilePath);
	// set buffer size to 1mb to execute progress function less often
	curl_setopt($ch, CURLOPT_BUFFERSIZE, 600000);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress');
	curl_setopt($ch, CURLOPT_NOPROGRESS, false);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	$output = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$filePathArray = preg_split("/\\//", $mediaFilePath);
	$mediaFileName = array_pop($filePathArray);

	if (!$output || $status != 200) {
		logMessage("Error: Audio file could not be downloaded (HTTP Error ".$status.").\n");
	} else {
		file_put_contents($conf["dir"]["cache"]."/".$mediaFileName, $output);
		logMessage("Audio file successfully downloaded.\n");
	}

}

$down = 0;

/**
 * @param $resource
 * @param $download_size
 * @param $downloaded
 * @param $upload_size
 * @param $uploaded
 * @return mixed
 */
function progress($resource,$download_size, $downloaded, $upload_size, $uploaded) {

	global $down;

	if ($download_size > 0 && $downloaded > ($down + 600000)) {
		$down = $downloaded + 600000;
		$progress = $downloaded / $download_size  * 100;
		logMessage("Media download progress: ".$progress."\n");
	}

}

/**
 * @param $mediaFilePath
 * @param $optimisedXMLFilePath
 * @param $outputFilePath
 * @return mixed
 */
function forceAlignMedia($mediaFilePath, $optimisedXMLFilePath, $outputFilePath,$sleep=1) {

	global $conf;
	
	if ($conf["serverType"] == 'windows') {
		$secureAudioPath = str_replace("/","\\",escapeshellcmd(__DIR__."\\".$mediaFilePath));
		$secureXMLPath = str_replace("/","\\",escapeshellcmd(__DIR__."\\".$optimisedXMLFilePath));
		$secureOutputPath = str_replace("/","\\",escapeshellcmd(__DIR__."\\".$outputFilePath));
	} else {
		$secureAudioPath = escapeshellcmd(__DIR__."/".$mediaFilePath);
		$secureXMLPath = escapeshellcmd(__DIR__."/".$optimisedXMLFilePath);
		$secureOutputPath = escapeshellcmd(__DIR__."/".$outputFilePath);
	}
	
	$exec_enabled =
		function_exists('exec') &&
		!in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions')))) &&
		strtolower(ini_get('safe_mode')) != 1
	;

	if (!$exec_enabled) {
		logMessage("PHP shell exec not allowed. Aeneas can not be executed.\n");
		exit();
	}

	if ($conf["serverType"] == 'windows') {
		putenv('PATH=$PATH;'.$conf["ffmpeg"].";".$conf["pythonDir"].";".$conf["pythonDirScripts"].";".$conf["eSpeak"]);
	} else {
		putenv('PATH=$PATH:'.$conf["pythonDir"]);
	}
	
	putenv('set PYTHONIOENCODING="UTF-8"');

	//$command = 'set PYTHONIOENCODING="UTF-8" && python -m aeneas.tools.execute_task '.$secureAudioPath.' '.$secureXMLPath.' "task_language=deu|os_task_file_format=json|is_text_type=unparsed|is_text_unparsed_id_regex=s[0-9]+|is_text_unparsed_id_sort=numeric|task_adjust_boundary_no_zero=true|task_adjust_boundary_nonspeech_min=2|task_adjust_boundary_nonspeech_string=REMOVE" '.$secureOutputPath.' -vv -l --log="F:\webdev\VideoTranscriptGenerator-master\www\admin\tmpaeneas.log" 2>&1';
	
	//$command = 'set PYTHONIOENCODING="UTF-8" && python -m aeneas.tools.execute_task '.$secureAudioPath.' '.$secureXMLPath.' "task_language=deu|os_task_file_format=json|is_text_type=unparsed|is_text_unparsed_id_regex=s[0-9]+|is_text_unparsed_id_sort=numeric|task_adjust_boundary_no_zero=true|task_adjust_boundary_nonspeech_min=2|task_adjust_boundary_nonspeech_string=REMOVE" '.$secureOutputPath.' 2>&1';
	$command = 'set PYTHONIOENCODING="UTF-8" && python -m aeneas.tools.execute_task '.$secureAudioPath.' '.$secureXMLPath.' "task_language=deu|os_task_file_format=json|is_text_type=unparsed|is_text_unparsed_id_regex=s[0-9]+|is_text_unparsed_id_sort=numeric|task_adjust_boundary_no_zero=false|task_adjust_boundary_nonspeech_min=2|task_adjust_boundary_nonspeech_string=REMOVE|task_adjust_boundary_nonspeech_remove=REMOVE|is_audio_file_detect_head_min=0.1|is_audio_file_detect_head_max=3|is_audio_file_detect_tail_min=0.1|is_audio_file_detect_tail_max=3|task_adjust_boundary_algorithm=aftercurrent|task_adjust_boundary_aftercurrent_value=0.5|is_audio_file_head_length=1" '.$secureOutputPath.' 2>&1';

	$output = exec($command,$foo);

	if (strpos($output, '[INFO] Created file ') !== false) {
		logMessage("Force align success. Aeneas Output: ".$output."\n");
	} else {
		logMessage("Force align error. Output: ".$output."\n");
	}

}

function disable_ob() {
	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Turn off PHP output compression
	ini_set('zlib.output_compression', false);
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);
	// Clear, and turn off output buffering
	while (ob_get_level() > 0) {
		// Get the curent level
		$level = ob_get_level();
		// End the buffering
		ob_end_clean();
		// If the current level has not changed, abort
		if (ob_get_level() == $level) break;
	}
	// Disable apache output buffering/compression
	if (function_exists('apache_setenv')) {
		apache_setenv('no-gzip', '1');
		apache_setenv('dont-vary', '1');
	}
}

?>