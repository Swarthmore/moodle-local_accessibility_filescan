# moodle-local_a11y_check

## A Moodle plugin that scans your course files for accessibility.

This plugin consists of a scheduled task that looks for PDFs within courses, evaluates their a11y, then saves the results to the Moodle database

## Dependencies

* Moodle 4.00+

## Installation

### Dependencies

`poppler-utils`

### From the command line 

```bash
git clone https://github.com/Swarthmore/moodle-local_a11y_check $MOODLE_WWW_ROOT/local/a11y_check
```

### How Does it Work?

This plugin will find PDFs across your Moodle instance, then scan them for accessibility. The plugin scans for the following: 

1. Does the PDF has OCR'd text?
2. Is the PDF tagged? 
3. Does the PDF have a language?
4. Does the PDF have a title?
5. Page count

PDFs are scanned at intervals in accordance to Moodle's cron system and results are stored in the plugin's database table. As such, to pull results, you will need to query the database, or use the [block_a11y_check](https://github.com/aweed1/moodle-block_a11y_check) plugin (which is recommended).
