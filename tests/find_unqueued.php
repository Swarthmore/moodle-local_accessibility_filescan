<?php

define('CLI_SCRIPT', 1);

require_once(__DIR__. "/../../../config.php");
require_once(__DIR__."/../classes/pdf.php");


// Task 1
// Get all PDFs that aren't currently in queue (the plugin does not know about them yet), then
// create a record of it.
echo "\nTask 1/3: get_unqueued()\n";
$files = \local_a11y_check\pdf::get_unqueued_files();
echo "Found " . count($files) . " records\n";

foreach ($files as $file) {
    \local_a11y_check\pdf::put_file_in_queue($file);
}

echo "Done!\n\n";


// Task 2
// Try to remove any orphaned files.
echo "Task 2/3: cleanup_orphaned_records\n";
\local_a11y_check\pdf::cleanup_orphaned_records();
echo "Done!\n\n";


// Task 3
// Try to find unscanned files.
echo "Task 3/3: get_unscanned_files()\n";
$unscanned = \local_a11y_check\pdf::get_unscanned_files();
echo "Found " . count($unscanned) . " records\n";
echo "Done!\n\n";


// Task 4
// Find 5 files to scan (FIFO). Scan them. Update/create their a11y results in the plugin tables.
echo "Task 3/4: a11y_check()\n";
\local_a11y_check\pdf::scan_queued_files();
echo "Done!\n\n";
