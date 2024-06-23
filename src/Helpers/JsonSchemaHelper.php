<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Helpers;

/**
 * Helper to manipulate JSON schemas.
 */
class JsonSchemaHelper {

  /**
   * Filter some data according to a JSON schema, removing undefined properties.
   *
   * @param array $data
   *   Data to filter.
   * @param array $properties
   *   Some JSON schema properties.
   *
   * @return array
   *   Filtered data.
   */
  protected function filterProperties(array $data, array $properties): array {
    if (empty($data) || empty($properties)) {
      return [];
    }
    foreach ($data as $key => $value) {
      if (!isset($properties[$key])) {
        unset($data[$key]);
      }
      elseif (is_array($value)) {
        $data[$key] = $this->filterProperties($value, $properties[$key]['properties'] ?? []);
      }
    }
    return $data;
  }

}
