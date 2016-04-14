# Import multiple events into the Hub API

## PHP script
```php
// require autoload
require "/vendor/autoload.php";

// settings
$settings = array(
  "prefix" => "earthday",
  "submitted_by" => "Earth Day import"
);

$importer = new \EventImporter\Importer("path/to/drupal", $settings);
$importer->import("path/to/csv");
```
