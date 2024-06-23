<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\Entity\EntityFormBuilder;

/**
 * Form builder for the ReliefWeb Resource entities.
 */
class ReliefWebResourceFormBuilder extends EntityFormBuilder {

  /**
   * {@inheritdoc}
   */
  public function getForm(EntityInterface $entity, $operation = 'default', array $form_state_additions = []) {
    print_r(["ReliefWebResourceFormBuilder"]); exit();
    return parent::getForm($entity, $operation, $form_state_additions);
  }

}
