<?php

namespace local_accessibility_filescan\external;

use \core_external\external_function_parameters;
use \core_external\external_multiple_structure;
use \core_external\external_single_structure;
use \core_external\external_value;

class get_results_by_course extends \core_external\external_api {

  /**
 * Returns description of method parameters
 * @return external_function_parameters
 */
  public static function execute_parameters() {
      return new external_function_parameters([
          'courseid' => new external_value(PARAM_INT, 'id of the course.')
      ]);
  }

  public static function execute(int $courseid) {
      global $DB;
      $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
      //$transaction = $DB->start_delegated_transaction(); //If an exception is thrown in the below code, all DB queries in this code will be rollback.

      $sql = 'select f.id as "fileid", f.filename as "filename", laftp.hastext as "hastext", laftp.hastitle as "hastitle", laftp.haslanguage as "haslanguage",  laftp.istagged as "istagged" '.
          'from {local_a11y_filescan_type_pdf} laftp '.
          'inner join {local_a11y_filescan_pivot} lafp on laftp.scanid = lafp.scanid '.
          'inner join {files} f on f.id = lafp.fileid '.
          'where lafp.courseid = '. $params['courseid'];

      $files = []; // Placeholder for returned files.

      $recordset = $DB->get_recordset_sql($sql);

      foreach ($recordset as $record) {
          $files[] = $record;
      }

      $recordset->close();

      return $files;
  }

  public static function execute_returns() {
      return new external_multiple_structure(
          new external_single_structure([
              'fileid' => new external_value(PARAM_INT, 'file id'),
              'filename' => new external_value(PARAM_TEXT, 'name of the file'),
              'hastext' => new external_value(PARAM_INT, 'does the file have readable text?'),
              'hastitle' => new external_value(PARAM_INT, 'does the file has a title?'),
              'haslanguage' => new external_value(PARAM_INT, 'does the file have a language?'),
              'istagged' => new external_value(PARAM_INT, 'is the file tagged?'),
          ])
      );
  }

}
