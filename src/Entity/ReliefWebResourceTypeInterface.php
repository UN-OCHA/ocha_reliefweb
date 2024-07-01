<?php

namespace Drupal\ocha_reliefweb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface defining a ReliefWeb Resource type entity.
 */
interface ReliefWebResourceTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Get wether the display of the page title is disabled or not.
   *
   * @return bool
   *   TRUE if the page title is disabled.
   */
  public function isPageTitleDisabled(): bool;

}
