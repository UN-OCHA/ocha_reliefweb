<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for the ReliefWeb Resource entities.
 */
class ReliefWebResourceTypeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Name');
    $header['description'] = [
      'data' => $this->t('Description'),
      'class' => [
        RESPONSIVE_PRIORITY_MEDIUM,
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => [
        'menu-label',
      ],
    ];
    $row['description']['data'] = [
      '#markup' => $entity->getDescription(),
    ];
    return $row + parent::buildRow($entity);
  }

}
