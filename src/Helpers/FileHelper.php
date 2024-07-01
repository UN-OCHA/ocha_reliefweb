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
    if (empty($uri)) {
      return '';
    }
    $uuid = preg_replace('#.+/([^.]+)\..+$#', '$1', $uri);
    return $uuid !== $uri ? $uuid : '';
  }

  /**
   * Extract the extension of the file.
   *
   * @param string $file_name
   *   File name.
   *
   * @return string
   *   File extension in lower case.
   */
  public static function extractFileExtension($file_name) {
    if (empty($file_name)) {
      return '';
    }
    return mb_strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
  }

}
