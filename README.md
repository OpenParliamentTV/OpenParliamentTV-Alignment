# Open Parliament TV Alignment

## Notes

1. Check if there are XML files in `input/` for which no corresponding mapping file exists under `output/FILENAME.json`.

2. If yes, check if file under `cache/FILENAME.mp3` or `cache/FILENAME.mp4` exists. 

3. If not, download file to cache/

```yaml
TEXT_XML_PATH = 'input/example.xml'
MEDIA_PATH = 'cache/example.mp3'
OUTPUT_PATH = 'output/example.json'

CONFIG_STRING = 'task_language=deu|os_task_file_format=json|is_text_type=unparsed|is_text_unparsed_id_regex=s[0-9]+|is_text_unparsed_id_sort=numeric|task_adjust_boundary_no_zero=false|task_adjust_boundary_nonspeech_min=2|task_adjust_boundary_nonspeech_string=REMOVE|task_adjust_boundary_nonspeech_remove=REMOVE|is_audio_file_detect_head_min=0.1|is_audio_file_detect_head_max=3|is_audio_file_detect_tail_min=0.1|is_audio_file_detect_tail_max=3|task_adjust_boundary_algorithm=percent|task_adjust_boundary_percent_value=75|is_audio_file_head_length=1'

set PYTHONIOENCODING="UTF-8" && python -m aeneas.tools.execute_task MEDIA_PATH TEXT_XML_PATH CONFIG_STRING OUTPUT_PATH
```