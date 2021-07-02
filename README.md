# Open Parliament TV Alignment

## Prerequisites

* [**Aeneas**](https://www.readbeyond.it/aeneas/) ("automagically synchronize audio and text")
    * Aeneas Dependencies: **Python** (2.7.x preferred), **FFmpeg**, and **eSpeak**

#### Installing Aeneas

See [https://github.com/readbeyond/aeneas/blob/master/wiki/INSTALL.md](https://github.com/readbeyond/aeneas/blob/master/wiki/INSTALL.md).

For Mac OS, there is an all-in-one installer, which takes care of the dependencies: [https://github.com/sillsdev/aeneas-installer/releases](https://github.com/sillsdev/aeneas-installer/releases).

## Notes

1. Check if there are `*.xml` files in `input/` for which no corresponding file exists under `output/INPUT_FILE_NAME.json` (replacing file suffix with `.json`).

2. If yes, parse input file (eg. `DE-0190003001_proceedings.xml`) as XML and retrieve `MEDIA_FILE_URI` at `html > body[data-media-file-uri]`.

3. Extract `MEDIA_FILE_NAME` from `MEDIA_FILE_URI`.

4. Check if file under `cache/MEDIA_FILENAME` exists (`.mp3` or `.mp4` file). 

5. If neither exists, download file at `MEDIA_FILE_URI` to cache/

6. Execute: 

```yaml
TEXT_XML_PATH = 'input/INPUT_FILE_NAME.xml'
MEDIA_PATH = 'cache/MEDIA_FILE_NAME'
OUTPUT_PATH = 'output/INPUT_FILE_NAME.json'

CONFIG_STRING = 'task_language=deu|os_task_file_format=json|is_text_type=unparsed|is_text_unparsed_id_regex=s[0-9]+|is_text_unparsed_id_sort=numeric|task_adjust_boundary_no_zero=false|task_adjust_boundary_nonspeech_min=2|task_adjust_boundary_nonspeech_string=REMOVE|task_adjust_boundary_nonspeech_remove=REMOVE|is_audio_file_detect_head_min=0.1|is_audio_file_detect_head_max=3|is_audio_file_detect_tail_min=0.1|is_audio_file_detect_tail_max=3|task_adjust_boundary_algorithm=percent|task_adjust_boundary_percent_value=75|is_audio_file_head_length=1'

set PYTHONIOENCODING="UTF-8" && python -m aeneas.tools.execute_task MEDIA_PATH TEXT_XML_PATH CONFIG_STRING OUTPUT_PATH
```

7. (Hook tells OPTV Platform there's a new alignment output file)

## Input file format

Input files are XML files in an XHTML format. The standalone file contains all necessary information to align a media source with text contents (using `html > body[data-media-file-uri]` as `MEDIA_PATH` and the XML file itself as `TEXT_XML_PATH` with the Aeneas settings: 

* is_text_type=unparsed
* is_text_unparsed_id_regex=s[0-9]+

**Example:** 

```yaml
<?xml version="1.0" encoding="UTF-8"?>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta charset="utf-8"/>
  </head>
  <body>
     <div data-media-file-uri="https://cldf-od.r53.cdn.tv1.eu/1000153copo/ondemand/app144277506/145293313/7174046/7174046_h264_720_400_2000kb_baseline_de_2192.mp4" data-media-id="DE-0190003001">
       <p data-type="speech">
         <span id="s000001">Guten Morgen, liebe Kolleginnen und Kollegen!</span>
         <span id="s000002">Nehmen Sie bitte Platz.</span>
       </p>
       <p data-type="comment">
         <span>(Lachen bei Abgeordneten der SPD)</span>
       </p>
     </div>
   </body>
 </html>
```

## Output file format

The JSON output generated by Aeneas should look like this: 

```yaml
{
  "fragments": [
    {
      "begin": "2.45768", 
      "children": [], 
      "end": "5.087213976", 
      "id": "s000001", 
      "language": "deu", 
      "lines": [
        "Guten Morgen, liebe Kolleginnen und Kollegen! "
      ]
    }, 
    {
      "begin": "5.092234", 
      "children": [], 
      "end": "7.197823", 
      "id": "s000002", 
      "language": "deu", 
      "lines": [
        "Nehmen Sie bitte Platz. "
      ]
    }
  ]
}
```
