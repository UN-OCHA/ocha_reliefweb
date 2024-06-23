<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Trait for classes that want to access the entity repository.
 */
trait ReliefWebEntityRepositoryTrait {

  /**
   * The entity repository.
   *
   * @var ?\Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The entity repository.
   *
   * @var ?\Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getEntityRepository(): EntityRepositoryInterface {
    if (!isset($this->entityRepository)) {
      $this->entityRepository = \Drupal::service('entity.repository');
    }
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeManager(): EntityTypeManagerInterface {
    if (!isset($this->entityRepository)) {
      $this->entityRepository = \Drupal::service('entity_type.manager');
    }
    return $this->entityRepository;
  }

}
