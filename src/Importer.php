<?php

namespace EventImporter;

class Importer
{
  /**
   * Constructor
   * @param  string $pathToDrupal Path to Drupal's base directory
   * @param  array  $settings     Additional settings
   * @return null
   */
  public function __construct($drupal, $settings = array())
  {
    // bootstrap Drupal
    define("DRUPAL_ROOT", $drupal);
    require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

    // create parser
    $this->parser = new Utils\CsvParser();

    // init settings
    $this->settings = $settings;
  }

  /**
   * Import an array of events
   * @param  string $url URL to Google spreadsheet
   * @return null
   */
  public function import($url)
  {
    $events = $this->parser->parse($url);

    foreach ($events as $i => $e) {

      // crete event
      $event = new Event($e, $this->settings);
      $node = $event->get();

      if (!$node) {
        echo "<strong>Event at index {$i} is missing required fields.</strong><br>";
      }

      // import into drupal
      node_save($node);
      echo "{$node->title} ({$node->nid}) imported.<br>";
    }
  }
}
