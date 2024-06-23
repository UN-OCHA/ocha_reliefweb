<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Config\ImmutableConfig;

/**
 * Interface for classes that want to access the config factory.
 */
interface ReliefWebConfigInterface {

  /**
   * Get the ReliefWeb API Client.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The ReliefWeb API client.
   */
  public function getConfig(string $name = 'ocha_reliefweb.settings'): ImmutableConfig;

}
