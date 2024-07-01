<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for the ReliefWeb Resource entities.
 */
class ReliefWebResourceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['type'] = $this->t('Type');
    $header['title'] = $this->t('Title');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Changed');
    $header['status'] = $this->t('Status');
    $header['message'] = $this->t('Mesage');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['uuid']['data'] = [
      '#markup' => $entity->bundle->entity->label(),
    ];

    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
    ] + $entity->toUrl()->toRenderArray();

    $row['created']['data'] = $entity->created->view([
      'label' => 'hidden',
    ]);

    $row['changed']['data'] = $entity->changed->view([
      'label' => 'hidden',
    ]);

    $row['status']['data'] = $entity->status->view([
      'label' => 'hidden',
    ]);

    $row['message']['data'] = $entity->message->view([
      'label' => 'hidden',
    ]);

    return $row + parent::buildRow($entity);
  }

}
