<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Trait for classes that want to access the config factory.
 */
trait ReliefWebConfigTrait {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory): void {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(string $name = 'ocha_reliefweb.settings'): ImmutableConfig {
    if (!isset($this->configFactory)) {
      $this->configFactory = \Drupal::service('config.factory');
    }
    return $this->configFactory->get($name);
  }

}
