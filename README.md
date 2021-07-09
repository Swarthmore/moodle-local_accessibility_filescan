# moodle-local_a11y_check

## A Moodle plugin that scans your course files for accessibility.

This plugin consists of a scheduled task that looks for PDFs within courses, evaluates their a11y, then saves the results to the Moodle database

## Dependencies

* Moodle 3.11+

## Installation

### Dependencies
`poppler-utils`

### From the command line 

```bash
git clone https://github.com/Swarthmore/moodle-local_a11y_check /moodle/root/dir/local/a11y_check
```

### A11y Report

This plugin provides the following endpoints to Moodle administrators. All URLs should be prefixed by your Moodle base URL (ie. https://moodle.mycollege.edu)

#### Provides details about scanned PDFs and their accessibility score.

```
/local/a11y_check/views/report.php
```

#### Download a CSV report containing scanned PDFs and their accessibility score.

```
/local/a11y_check/views/download_report.php
```
