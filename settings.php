<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language file for local_a11y_check
 *
 * @package   local_a11y_check
 * @copyright 2020 Swarthmore College
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
  
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_a11y_check', get_string('pluginname', 'local_a11y_check'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_a11y_check/api_url',
        get_string('settings:api_url', 'local_a11y_check'),
        get_string('settings:api_url_desc', 'local_a11y_check'),
        '', PARAM_TEXT, 128));

    $settings->add(new admin_setting_configtext('local_a11y_check/api_token',
        get_string('settings:api_key', 'local_a11y_check'),
        get_string('settings:api_key_desc', 'local_a11y_check'),
        '', PARAM_TEXT, 128));

    $settings->add(new admin_setting_configtext('local_a11y_check/files_per_cron',
        get_string('settings:files_per_cron', 'local_a11y_check'),
        get_string('settings:files_per_cron_desc', 'local_a11y_check'),
        5, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_a11y_check/max_file_size_mb',
        get_string('settings:max_file_size_mb', 'local_a11y_check'),
        get_string('settings:max_file_size_mb_desc', 'local_a11y_check'),
        500, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_a11y_check/max_retries',
        get_string('settings:max_retries', 'local_a11y_check'),
        get_string('settings:max_retries_desc', 'local_a11y_check'),
        '3', PARAM_TEXT, 128));

    $settings->add(new admin_setting_configtext('local_a11y_check/text_check_help',
        get_string('settings:text_check_help_desc', 'local_a11y_check'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60));

    $settings->add(new admin_setting_configtext('local_a11y_check/title_check_help',
        get_string('settings:title_check_help_desc', 'local_a11y_check'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60));

    $settings->add(new admin_setting_configtext('local_a11y_check/lang_check_help',
        get_string('settings:lang_check_help_desc', 'local_a11y_check'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60));

    $settings->add(new admin_setting_configtext('local_a11y_check/outline_check_help',
        get_string('settings:outline_check_help_desc', 'local_a11y_check'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60));
}