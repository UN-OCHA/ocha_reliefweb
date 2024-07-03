<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Interface for classes that want to access the config factory.
 */
interface ReliefWebConfigInterface {

  /**
   * Set the config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory): void;

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
