<?php

namespace EventImporter\Utils;

class TermsLoader
{
  public function __construct()
  {

  }

  /**
   * Load taxonomy terms by ID from a specific vocabulary
   * @param  string $vocab Vocabulary name
   * @param  array  $ids   Term IDs
   * @return array  Valid term IDs
   */
  public function load($vocab, $ids = array())
  {
    // get all terms
    $terms = taxonomy_term_load_multiple($ids);

    // find ones that aren't in the correct vocab
    $trash = array();
    foreach ($terms as $id => $term) {
      if ($term->vocabulary_machine_name != $vocab) {
        $trash[] = $id;
      }
    }

    // throw out invalid terms
    foreach ($trash as $id) {
      unset($terms[$id]);
    }

    return array_keys($terms);
  }

}
