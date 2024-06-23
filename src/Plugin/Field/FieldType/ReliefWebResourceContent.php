<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * The 'reliefweb_resource_content' field type plugin.
 *
 * @FieldType(
 *   id = "reliefweb_resource_content",
 *   label = @Translation("ReliefWeb Resource Content"),
 *   description = @Translation("Stores JSON encoded submitted content."),
 *   default_widget = "reliefweb_resource_content_default_widget",
 *   default_formatter = "reliefweb_resource_content_default_formatter",
 *   list_class = "\Drupal\ocha_reliefweb\Plugin\Field\FieldType\ReliefWebResourceContentList",
 *   cardinality = 1
 * )
 */
class ReliefWebResourceContent extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = MapDataDefinition::create()
      ->setLabel(new TranslatableMarkup('JSON encoded submitted data'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return empty($value);
  }

}
