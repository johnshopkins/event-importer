<?php

namespace EventImporter;

class Event
{
  /**
   * Event slug prefix
   * @var string
   */
  protected $prefix = null;

  /**
   * Who submitted the event
   * @var string
   */
  protected $submitted_by = null;

  /**
   * Event slug prefix
   * @var string
   */
  public function __construct($event = array(), $settings = array())
  {
    $this->event = $event;

    if (isset($settings["prefix"])) {
      $this->prefix = $settings["prefix"] . "-";
    }

    if (isset($settings["submitted_by"])) {
      $this->submitted_by = $settings["submitted_by"];
    }
  }

  public function get()
  {
    $this->setupBaseNode();
    $valid = $this->isValid($this->event);

    if (!$valid) return null;

    foreach ($this->event as $key => $value) {

      $methodName = "parse__{$key}";
      if (method_exists($this, $methodName)) {
        $this->$methodName($value);
      }

    }

    return $this->node;
  }

  /**
   * Make sure the event has all the required fields
   * @param  array $event Array of key=value pairs
   * @return boolean
   */
  protected function isValid($event)
  {
    // get rid of empty values
    $event = array_filter($event);

    $required = array("title", "who_can_attend", "start_date");
    $given = array_keys($event);

    $intersect = array_intersect($required, $given);

    return count($required) == count($intersect);
  }

  protected function setupBaseNode()
  {
    $this->node = new \StdClass();
    $this->node->type = "event";

    // fill in some default values
    node_object_prepare($this->node);

    $this->node->language = LANGUAGE_NONE;
    $this->node->field_approved[$this->node->language][0]["value"] = 0;

    if ($this->submitted_by) {
      $this->node->field_submitted_by_name[$this->node->language][0]["value"] = $this->submitted_by;
    }
  }

  protected function getFormattedDate($date)
  {
    $date = strtotime($date);
    return date("Y-m-d", $date) . " 00:00:00";
  }

  /**
   * Replace single line breaks with "  \n" so markdown knows we want
   * a line break. See: http://daringfireball.net/projects/markdown/syntax#p
   * @param  string $value
   * @return $string
   */
  protected function addLineBreaks($value)
  {
    return preg_replace("/\n(?!\n)/", "  \n", $value);
  }

  protected function parse__title($value)
  {
    // createSlug() is defined in safety module
    $this->node->title = $this->prefix . createSlug($value);
    $this->node->field_headline[$this->node->language][0]["value"] = $value;
  }

  protected function addTerms($value, $taxonomy)
  {
    $value = preg_replace("/\s/", "", $value);
    $terms = explode(",", $value);

    if (empty($terms)) return;

    // format terms for drupal
    $terms = array_map(function ($term) {
      return array("tid" => $term);
    }, $terms);

    // add terms to field
    $fieldName = "field_{$taxonomy}";
    $this->node->{$fieldName} = array();
    $this->node->{$fieldName}[$this->node->language] = $terms;
  }

  protected function parse__categories($value)
  {
    $this->addTerms($value, "category");
  }

  protected function parse__tags($value)
  {
    $this->addTerms($value, "tags");
  }

  protected function parse__divisions($value)
  {
    $this->addTerms($value, "divisions");
  }

  protected function parse__channels($value)
  {
    $this->addTerms($value, "channels");
  }

  protected function parse__free($value)
  {
    $value = strtolower($value);
    $accepted = array("yes", "free");

    if (in_array($value, $accepted)) {
      $this->node->field_free[$this->node->language][0]["value"] = 1;
    }
  }

  protected function parse__ticket_info($value)
  {
    $value = $this->addLineBreaks($value);
    $this->node->field_ticket_information[$this->node->language][0]["value"] = $value;
    $this->node->field_ticket_information[$this->node->language][0]["format"] = "markdown_enabled";
  }

  protected function parse__registration_required($value)
  {
    $value = strtolower($value);
    $accepted = array("yes");

    if (in_array($value, $accepted)) {
      $this->node->field_registration_required[$this->node->language][0]["value"] = 1;
    }
  }

  protected function parse__registration_info($value)
  {
    $value = $this->addLineBreaks($value);
    $this->node->field_registration_information[$this->node->language][0]["value"] = $value;
    $this->node->field_registration_information[$this->node->language][0]["format"] = "markdown_enabled";
  }

  protected function parse__body($value)
  {
    $value = $this->addLineBreaks($value);
    $this->node->body[$this->node->language][0]["value"]   = $value;
    $this->node->body[$this->node->language][0]["format"]  = "markdown_enabled";
  }

  protected function parse__summary($value)
  {
    $value = $this->addLineBreaks($value);
    $this->node->field_summary[$this->node->language][0]["value"] = $value;
    $this->node->field_summary[$this->node->language][0]["format"] = "markdown_enabled";
  }

  protected function parse__who_can_attend($value)
  {
    $value = preg_replace("/\s/", "", $value);
    $audiences = explode(",", $value);

    // if "everyone" is one of the values, add faculty, staff, students
    if (in_array("everyone", $audiences)) {
      $audiences = array_merge($audiences, array("faculty", "staff", "students"));
    }

    $accepted = array("faculty", "staff", "students", "everyone");

    // format for drupal
    $audiences = array_map(function ($audience) use ($accepted) {

      $audience = strtolower($audience);
      return in_array($audience, $accepted) ? array("value" => $audience) : null;

    }, $audiences);

    // add to field
    $this->node->field_open_to = array();
    $this->node->field_open_to[$this->node->language] = $audiences;
  }

  protected function parse__start_date($value)
  {
    $this->node->field_start_date[$this->node->language][0]["value"] = $this->getFormattedDate($value);
  }

  protected function parse__start_time($value)
  {
    $this->node->field_start_time[$this->node->language][0]["value"] = $value;
  }

  protected function parse__end_date($value)
  {
    $this->node->field_end_date[$this->node->language][0]["value"] = $this->getFormattedDate($value);
  }

  protected function parse__end_time($value)
  {
    $this->node->field_end_time[$this->node->language][0]["value"] = $value;
  }

  protected function parse__location($value)
  {
    $this->node->field_location[$this->node->language][0]["tid"] = $value;
  }

  protected function parse__additional_location_info($value)
  {
    $this->node->field_additional_location_info[$this->node->language][0]["value"] = $value;
  }

  protected function parse__contact_name($value)
  {
    $this->node->field_contact_name[$this->node->language][0]["value"] = $value;
  }

  protected function parse__contact_email($value)
  {
    $this->node->field_contact_email[$this->node->language][0]["value"] = $value;
  }

  protected function parse__contact_phone($value)
  {
    $this->node->field_contact_phone[$this->node->language][0]["value"] = $value;
  }

  protected function parse__contact_url($value)
  {
    $this->node->field_contact_url[$this->node->language][0]["value"] = $value;
  }

  protected function parse__facebook_url($value)
  {
    $this->node->field_facebook_url[$this->node->language][0]["value"] = $value;
  }
}
