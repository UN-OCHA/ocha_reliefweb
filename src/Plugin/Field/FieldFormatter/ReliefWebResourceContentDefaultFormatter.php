<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ocha_reliefweb\ReliefWebApiClientInterface;
use Drupal\ocha_reliefweb\ReliefWebApiClientTrait;
use Drupal\ocha_reliefweb\ReliefWebConfigInterface;
use Drupal\ocha_reliefweb\ReliefWebConfigTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class ReliefWebResourceContentDefaultFormatter extends FormatterBase implements ReliefWebApiClientInterface, ReliefWebConfigInterface {

  use MessengerTrait;
  use ReliefWebApiClientTrait;
  use ReliefWebConfigTrait;

  /**
   * Constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    MessengerInterface $messenger,
    TranslationInterface $string_translation,
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
    );
    $this->setMessenger($messenger);
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('string_translation'),
    );
  }

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
