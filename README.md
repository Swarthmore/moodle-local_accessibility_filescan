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

To view the a11y report for scanned PDFs, you must be an administrator for your Moodle instance. Head to `/local/a11y_check/views/report.php` to view.