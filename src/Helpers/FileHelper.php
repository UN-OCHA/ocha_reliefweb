<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Helpers;

/**
 * Helper to manipulate files.
 */
class FileHelper {

  /**
   * Get a file's UUID from its URI.
   *
   * @param string $uri
   *   File URI.
   *
   * @return string
   *   UUID.
   */
  public static function getFileUuidFromUri(string $uri): string {
    if (empt($uri)) {
      return '';
    }
    $uuid = preg_replace('#.+/([^.]+)\..+$#', '$1', $uri);
    return $uuid !== $uri ? $uuid : '';
  }

}
