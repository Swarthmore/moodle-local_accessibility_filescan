# moodle-local_a11y_check

## A Moodle plugin that scans your course files for accessibility.

This plugin consists of a scheduled task that looks for PDFs within courses, evaluates their a11y, then saves the results to the Moodle database

## Dependencies

* Moodle 3.11+

## Installation

### From the command line 

```sh
MOODLE=/path/to/moodle
REPO=https://github.com/Swarthmore/moodle-local_a11y_check
git clone $REPO $MOODLE/local/a11y_check
```
