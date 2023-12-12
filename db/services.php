<?php

defined('MOODLE_INTERNAL') || die;

$functions = [
    // The name of your web service function, as discussed above.
    'local_accessibility_filescan_get_results_by_course' => [
        // The name of the namespaced class that the function is located in.
        'classname'   => 'local_accessibility_filescan\external\get_results_by_course',

        // A brief, human-readable, description of the web service function.
        'description' => 'Get results of the accessibility fielscan plugin by course id.',

        // Options include read, and write.
        'type'        => 'read',

        // Whether the service is available for use in AJAX calls from the web.
        'ajax'        => true,

        // An optional list of services where the function will be included.
        'services' => [
            // A standard Moodle install includes one default service:
            // - MOODLE_OFFICIAL_MOBILE_SERVICE.
            // Specifying this service means that your function will be available for
            // use in the Moodle Mobile App.
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ]
    ],
];
