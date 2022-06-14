<?php


error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
set_time_limit(0);
date_default_timezone_set('CET');
ob_implicit_flush(true);
ob_end_flush();

require_once("config.php");
include_once("alignment.php");

$filter =  "~^20.*\.json$~";
//$filter =  "~^20003.*\.json$~";
//$filter = "~^19.*\.json$~";

function getFilesWithDate($dir, $fileFilter = "~\.json$~") {

    $files = scandir($dir);

    foreach ($files as $f) {
        if (($f != "." && $f != ".." && !is_dir($dir."/".$f) && preg_match($fileFilter, $f) === 1)) {
            $files_current[$f] = filemtime($dir."/".$f);
        }
    }

    return $files_current;

}

//queue Index
if (!file_exists(__DIR__."/queue-index.json")) {
    file_put_contents(__DIR__."/queue-index.json", json_encode(array()));
}
$queueIndex = json_decode(file_get_contents(__DIR__."/queue-index.json"),true);


//If there is no input dir, create it
if (!is_dir($conf["git"]["localPath"])) {

    mkdir($conf["git"]["localPath"]);
    $files = array();
} else {

    //Get the current last edit dates of all *.json files
    $files = getFilesWithDate($conf["git"]["localPath"], $filter);

}

foreach ($files as $k=>$fd) {

    //if ((array_key_exists($k,$queueIndex) === false)
    $tmpFile = json_decode(file_get_contents($conf["git"]["localPath"]."/".$k),true);
    foreach ($tmpFile as $speech) {

        //TODO: Check for errors and decide if set it back to todo
        if (!$queueIndex[$k][$speech["media"]["sourcePage"]]) {
            $queueIndex[$k][$speech["media"]["sourcePage"]] = "todo";
        }
    }


}


/*
 * TODO: Cleaner-Job, find out schedule
 * Massive performance improvement if we do it.
 * Todo: Garbagecollector for temp files which aeneas needs for alignment
 *
foreach ($queueIndex as $tmpK=>$file) {
    $tmpCnt = 0;
    $tmpLength = count($file);
    print_r($tmpLength);
    foreach ($file as $k=>$v) {
        if ($v=="done") {
            $tmpCnt++;
        }
    }
    echo $tmpLength;
    echo "<br><br>";
    if ($tmpCnt == $tmpLength) {
        unset($queueIndex[$tmpK]);
    }
}
*/


file_put_contents(__DIR__."/queue-index.json",json_encode($queueIndex,JSON_PRETTY_PRINT));

if (!is_dir($conf["dir"]["output-json"])) {
    mkdir($conf["dir"]["output-json"]);
}



foreach ($files as $fileName=>$fileDate) {

    $tmpFile = json_decode(file_get_contents($conf["git"]["localPath"]."/".$fileName),true);

    if (file_exists($conf["dir"]["output-json"]."/".$fileName)) {

        $compiledFile = json_decode(file_get_contents($conf["dir"]["output-json"]."/".$fileName),true);

    } else {

        $compiledFile = array();

    }


    foreach ($tmpFile as $speechKey=>$speech) {

        if (($queueIndex[$fileName][$speech["media"]["sourcePage"]] == "todo") || ($queueIndex[$fileName][$speech["media"]["sourcePage"]] == "error")) {

            if (isset($speech["textContents"]) && count($speech["textContents"]) != 0) {

                $textObject = json_encode($speech["textContents"][0], true);

                $tempFilenameForXMP = preg_replace("~\.~","-",microtime(true))."_".$speech["textContents"][0]["type"].".xml";
                $tempFilePathForXMP = $conf["dir"]["input"]."/".$tempFilenameForXMP;
                $alignmentInputXML = textObjectToAlignmentInput($textObject, $speech["media"]["audioFileURI"], $speech["media"]["videoFileURI"]);

                file_put_contents($tempFilePathForXMP, $alignmentInputXML);

                try {
                    $response = forceAlignXMLData($tempFilenameForXMP,$conf["sleep"]);
                    if ($response["success"] == "true") {

                        //TODO: aufräumen und files updaten (queue und merged.json)
                        $alignmentOutput = file_get_contents($response["filename"]);
                        $queueIndex[$fileName][$speech["media"]["sourcePage"]] = "done";
                        $tmpFile[$speechKey]["textContents"][0] = mergeAlignmentOutputWithTextObject($alignmentOutput,$textObject);
                        $tmpFile[$speechKey]["media"]["aligned"] = 1;
                        $error = false;

                    } else {
                        $queueIndex[$fileName][$speech["media"]["sourcePage"]] = "error";
                        logMessage("Error processing file: Custom error in file ".$fileName."\n");
                        $error = true;

                    }

                } catch (Exception $e) {
                    logMessage("Error processing file:".$fileName."\n".$e->getFile()."\n"."Line: ".$e->getFile()."\n".$e->getMessage()."\n");

                    $error = true;
                }

                if ($error) {
                    logMessage("Error: Indexing queue could not be completed. File:".$fileName."\n");
                } else {
                    logMessage("\nSuccess: finished processing queue. File:".$fileName."\n\n");
                }

            }




        } elseif ($queueIndex[$fileName][$speech["media"]["sourcePage"]] == "done") {
            $tmpFound = 0;
            foreach ($compiledFile as $k=>$v) {
                if ($v["media"]["sourcePage"] == $tmpFile[$speechKey]["media"]["sourcePage"]) {
                    $tmpFile[$speechKey]["textContents"][0] = $v["textContents"][0];
                    $tmpFile[$speechKey]["media"]["aligned"] = 1;
                    $tmpFound++;
                    break;
                }
            }

            if ($tmpFound != 1) {
                logMessage("\nError: Speech was marked as DONE was not found in output json. File: ".$fileName." | Speech: ".$speech["media"]["sourcePage"]."\n\n");
            }

        }

        file_put_contents($conf["dir"]["output-json"]."/".$fileName, json_encode($tmpFile,JSON_PRETTY_PRINT));
        file_put_contents(__DIR__."/queue-index.json",json_encode($queueIndex,JSON_PRETTY_PRINT));


    }

}



?>
