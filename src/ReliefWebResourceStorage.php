<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the ReliefWeb Resource entity storage.
 */
class ReliefWebResourceStorage extends SqlContentEntityStorage implements ReliefWebResourceStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    parent::save($entity);

    // Run any post saving processing.
    $entity->afterSave();
  }

}
