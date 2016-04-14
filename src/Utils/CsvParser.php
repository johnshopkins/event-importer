<?php

namespace EventImporter\Utils;

class CsvParser
{
  public function __construct() {
    ini_set("auto_detect_line_endings", true);
    mb_internal_encoding("ISO-8859-1");
  }

  public function parse($path)
  {
    return $this->csvToArray($path);
  }

  protected function parseCsv($file)
  {
    $handle = fopen($file, "r");
    $data = array();

    if ($handle !== false) {
      while (($row = fgetcsv($handle, 1000, ",")) !== false) {
        $data[] = $row;
        }
    }

    fclose($handle);
    return $data;
  }

  protected function csvToArray($file)
  {
    $data = $this->parseCsv($file);

    $rows = array_shift($data);
    $rows = array_map(function($value) {
      $value = strtolower($value);
      return str_replace(" ", "_", $value);
    }, $rows);

    return array_map(function($column) use ($rows) {
      $a = array();
      for($i = 0; $i < count($column); $i++) {
        $a[$rows[$i]] = !empty($column[$i]) ? $column[$i] : null;
      }
      return $a;
    }, $data);
  }

}
