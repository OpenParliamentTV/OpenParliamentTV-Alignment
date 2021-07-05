<?php

$conf["dir"]["input"] = "input";
$conf["dir"]["output"] = "output";
$conf["dir"]["cache"] = "cache";

$conf["sleep"] = 0;

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    
    $conf["serverType"] = "windows";

    $conf["pythonDir"] = "C:\Python27";
	$conf["pythonDirScripts"] = "C:\Python27\Scripts";
	$conf["eSpeak"] = "C:\Program Files (x86)\\eSpeak\command_line";
	$conf["ffmpeg"] = "C:\Program Files (x86)\FFmpeg\bin";

} else {
    
    $conf["serverType"] = "unix";

    $conf["pythonDir"] = "/usr/local/bin";
	$conf["pythonDirScripts"] = "/usr/local/bin";
	$conf["eSpeak"] = "/usr/local/bin";
	$conf["ffmpeg"] = "/usr/local/bin";

}

?>