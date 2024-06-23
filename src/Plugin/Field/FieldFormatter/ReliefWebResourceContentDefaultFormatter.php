<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * The 'reliefweb_resource_content_default_formatter' formatter plugin.
 *
 * @FieldFormatter(
 *   id = "reliefweb_resource_content_default_formatter",
 *   label = @Translation("ReliefWeb Resource Content Default Formatter"),
 *   field_types = {
 *     "reliefweb_resource_content"
 *   }
 * )
 */
class ReliefWebResourceContentDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_entity_type_id = $field_definition->getTargetEntityTypeId();
    return $target_entity_type_id === 'reliefweb_resource';
  }

}
