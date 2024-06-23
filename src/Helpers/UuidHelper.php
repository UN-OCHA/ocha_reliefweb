<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Helpers;

use Symfony\Component\Uid\Uuid;

/**
 * Helper to manipulate UUIDs.
 */
class UuidHelper {

  /**
   * Generate a UUID V5 for the data and namespace.
   *
   * @param string $namespace
   *   Namespace for the UUID.
   * @param string $data
   *   Data (ex: URL) for which to generate a UUID.
   *
   * @return string
   *   UUID.
   */
  public static function generateUuidV5(string $namespace, string $data): string {
    return Uuid::v5(Uuid::fromString($namespace), $data)->toRfc4122();
  }

  /**
   * Check if a UUID is a version 5 UUID.
   *
   * @param string $uuid
   *   The UUID.
   *
   * @return bool
   *   TRUE if version 5.
   */
  public static function isUuidV5(string $uuid): bool {
    return $uuid[14] === '5';
  }

  /**
   * Check if a UUID is valid.
   *
   * @param string $uuid
   *   The UUID.
   *
   * @return bool
   *   TRUE if valid.
   */
  public static function isUuidValid(string $uuid): bool {
    return Uuid::isValid($uuid);
  }

}
