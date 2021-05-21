# moodle-local_a11y_check

## A Moodle plugin that scans your course files for accessibility.

This plugin consists of: 

* A scheduled task that looks for PDFs within courses, sends them to an external server to check for accessibilty, and saves the results to the Moodle database

## Dependencies

* Moodle 3.1+
* [filescan-server](https://github.com/Swarthmore/filescan-server/) is required to scan your Moodle's PDF files.

## Installation

### From the command line 

```sh
MOODLE=/path/to/moodle
REPO=https://github.com/Swarthmore/moodle-local_a11y_check
git clone $REPO $MOODLE/local/a11y_check
```
