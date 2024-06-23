<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Helpers;

/**
 * Helper to manipulate classes.
 */
class ClassHelper {

  /**
   * Check if a class exists, converting the given class name to camel case.
   *
   * @param string $namespace
   *   Namespace.
   * @param string $classname
   *   Class name.
   *
   * @return string|false
   *   Namespaced class name or FALSE if it was not found.
   */
  public static function classExists(string $namespace, string $classname): string|false {
    $class = rtrim($namespace, '\\') . '\\' . static::toCamelCase($classname);
    return class_exists($class) ? $class : FALSE;
  }

  /**
   * Convert the given string to camel case.
   *
   * @param string $string
   *   String to convert to camelcase.
   *
   * @return string
   *   Converted string.
   */
  public static function toCamelCase(string $string): string {
    return str_replace(['_', '-', ' '], '', ucwords($string, '_- '));
  }

}
