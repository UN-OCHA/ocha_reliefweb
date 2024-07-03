<?php

namespace Drupal\ocha_reliefweb\Entity;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface defining a ReliefWeb Resource type entity.
 */
interface ReliefWebResourceTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Get the entity bundle custom settings.
   *
   * @return array
   *   The settings.
   */
  public function getSettings(): array;

  /**
   * Get a custom setting.
   *
   * @param string $key
   *   Setting name.
   * @param mixed $default
   *   Default value if there is no setting.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting(string $key, mixed $default = NULL): mixed;

  /**
   * Get wether the display of the page title is disabled or not.
   *
   * @return bool
   *   TRUE if the page title is disabled.
   */
  public function isPageTitleDisabled(): bool;

  /**
   * Get the warning message to display when previewing a submission.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The warning message
   */
  public function getPreviewWarning(): MarkupInterface|string;

}
