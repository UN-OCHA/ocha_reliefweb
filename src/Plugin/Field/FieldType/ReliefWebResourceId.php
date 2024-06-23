<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\UuidItem;

/**
 * The 'reliefweb_resource_content' field type plugin.
 *
 * @FieldType(
 *   id = "reliefweb_resource_id",
 *   label = @Translation("ReliefWeb Resource ID"),
 *   description = @Translation("Stores the ID of a ReliefWeb resource."),
 *   no_ui = TRUE,
 *   default_formatter = "string",
 *   cardinality = 1
 * )
 */
class ReliefWebResourceId extends UuidItem {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    $this->setValue(['value' => NULL], $notify);
    return $this;
  }

}
