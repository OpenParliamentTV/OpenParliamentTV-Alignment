<?php


error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
set_time_limit(0);
date_default_timezone_set('CET');
ob_implicit_flush(true);
ob_end_flush();

require_once "config.php";
include_once("alignment.php");



function getFilesWithDate($dir) {

    $files = scandir($dir);

    foreach ($files as $f) {
        if (($f != "." && $f != ".." && !is_dir($dir."/".$f) && preg_match("~\.json$~", $f) === 1)) {
            $files_current[$f] = filemtime($dir."/".$f);
        }
    }

    return $files_current;

}


//If there is no repository yet, get it.
if (!is_dir($conf["git"]["localPath"])) {
    $cmd = $conf["git"]["exec"]." clone ".$conf["git"]["repository"]." ".$conf["git"]["localPath"];
    exec($cmd);
    $files_current = array();
} else {

    //Get the current last edit dates of all *.json files
    $files_current = getFilesWithDate($conf["git"]["localPath"]);

}



//Get the current last edit dates of all *.json files
//$files_current = json_decode(file_get_contents(__DIR__."/file-index.json"));

//Pull the updates
exec($conf["git"]["exec"]." -C ".$conf["git"]["localPath"]." remote update");
exec($conf["git"]["exec"]." -C ".$conf["git"]["localPath"]." pull");
$files_new = getFilesWithDate($conf["git"]["localPath"]);

//queue Index
if (!file_exists(__DIR__."/queue-index.json")) {
    file_put_contents(__DIR__."/queue-index.json", json_encode(array()));
}
$queueIndex = json_decode(file_get_contents(__DIR__."/queue-index.json"),true);

foreach ($files_new as $k => $fd) {
    if ((array_key_exists($k,$files_current) === false) || ($fd>$files_current[$k])) {
        $tmpFile = json_decode(file_get_contents($conf["git"]["localPath"]."/".$k),true);

        echo "<pre>";
        foreach ($tmpFile as $speech) {
            if (!$queueIndex[$k][$speech["media"]["sourcePage"]]) {
                $queueIndex[$k][$speech["media"]["sourcePage"]] = "todo";
            }
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

foreach ($queueIndex as $filename=>$speechSourcePages) {
    echo "filename:";
    print_r($filename);
    echo "file:";
    print_r($speechSourcePages);

    $inputJson = json_decode(file_get_contents($conf["git"]["localPath"]."/".$filename),true);

    foreach ($speechSourcePages as $sourcePage=>$status) {

        if ($status == "todo") {
            foreach ($inputJson as $speechKey => $speech) {
                if ($speech["media"]["sourcePage"] == $sourcePage) {
                    //This is the right speech to send to aeneas due to the todo queue

                    /*
                     * TODO: Check which type is proceedings, for now we take [0]
                     *

                     foreach ($speech as $textContents) {
                         foreach ($textContents as $textContent) {
                            if ()
                        }
                    }
                    */

                    if (isset($speech["textContents"]) && count($speech["textContents"]) != 0) {

                        $mediaFileURI = ($speech["media"]["audioFileURI"]) ? $speech["media"]["audioFileURI"] : $speech["media"]["videoFileURI"];

                        $textObject = json_encode($speech["textContents"][0], true);


                        $tempFilenameForXMP = preg_replace("~\.~","-",microtime(true))."_".$speech["textContents"][0]["type"].".xml";
                        $tempFilePathForXMP = $conf["dir"]["input"]."/".$tempFilenameForXMP;
                        $alignmentInputXML = textObjectToAlignmentInput($textObject, $mediaFileURI);
                        file_put_contents($tempFilePathForXMP, $alignmentInputXML);
                        try {
                            $response = forceAlignXMLData($tempFilenameForXMP,$conf["sleep"]);
                            if ($response["success"] == "true") {

                                //TODO: aufräumen und files updaten (queue und merged.json)
                                $alignmentOutput = file_get_contents($response["filename"]);
                                $queueIndex[$filename][$sourcePage] = "done";
                                $inputJson[$speechKey]["textContents"][0] = mergeAlignmentOutputWithTextObject($alignmentOutput,$textObject);
                                $inputJson[$speechKey]["media"]["aligned"] = 1;


                            } else {
                                $queueIndex[$filename][$sourcePage] = "error";
                                logMessage("Error processing file: Custom error\n");

                            }

                        } catch (Exception $e) {
                            logMessage("Error processing file:\n".$e->getFile()."\n"."Line: ".$e->getFile()."\n".$e->getMessage()."\n");

                            $error = true;
                        }

                        if ($error) {
                            logMessage("Error: Indexing queue could not be completed\n");
                        } else {
                            logMessage("\nSuccess: finished processing queue\n\n");
                        }

                    }


                }
            }




        }
    }

    if (!is_dir($conf["dir"]["output-json"])) {
        mkdir($conf["dir"]["output-json"]);
    }
    file_put_contents($conf["dir"]["output-json"]."/".$filename, json_encode($inputJson,JSON_PRETTY_PRINT));
    file_put_contents(__DIR__."/queue-index.json",json_encode($queueIndex,JSON_PRETTY_PRINT));

}





?>