<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldWidget;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ocha_reliefweb\Helpers\LocalizationHelper;
use Drupal\ocha_reliefweb\ReliefWebApiClientInterface;
use Drupal\ocha_reliefweb\ReliefWebApiClientTrait;
use Drupal\ocha_reliefweb\ReliefWebConfigInterface;
use Drupal\ocha_reliefweb\ReliefWebConfigTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The 'reliefweb_resource_content_default_widget' widget plugin.
 *
 * @FieldWidget(
 *   id = "reliefweb_resource_content_default_widget",
 *   label = @Translation("ReliefWeb Resource Content default Widget"),
 *   field_types = {
 *     "reliefweb_resource_content"
 *   }
 * )
 */
class ReliefWebResourceContentDefaultWidget extends WidgetBase implements ReliefWebApiClientInterface, ReliefWebConfigInterface {

  use ReliefWebApiClientTrait;
  use ReliefWebConfigTrait;

  /**
   * Constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#element_validate'][] = [$this, 'validateElement'];
    return $element;
  }

  /**
   * Validate the form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The full form.
   */
  public function validateElement(array &$element, FormStateInterface $form_state, array $form) {
    $entity = $form_state->getFormObject()->getEntity();
    $parents = array_merge($element['#parents'], ['value']);
    $values = $form_state->getValue($parents);
    $data = $this->getDataFromForm($values, $form, $form_state);
    $errors = $entity->validateSchema($data);

    foreach ($errors as $key => $messages) {
      $name = implode('][', array_merge($parents, array_filter(explode('/', $key))));
      foreach ($messages as $message) {
        $form_state->setErrorByName($name, $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      $values[$delta]['value'] = $this->getDataFromForm($value['value'], $form, $form_state);
    }

    return $values;
  }

  /**
   * Create a form element that gets its data from the ReliefWeb API.
   *
   * @param string $resource
   *   ReliefWeb API resource.
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Label for the form element.
   * @param string|\Drupal\Component\Render\MarkupInterface $description
   *   Description for the form element.
   * @param array $filter
   *   Additional filtering on the resource (ex: only ongoing disasters).
   * @param array $fields
   *   List of extra fields to return in addition to the ID and name.
   * @param int $limit
   *   Number of terms to retrieve. (Defaults to 10000 to retrieve everything).
   * @param ?array $default_value
   *   Default value.
   * @param array $properties
   *   Extra properties to add to the form element.
   *
   * @return array
   *   The selector form element.
   */
  protected function createTermSelector(
    string $resource,
    string|MarkupInterface $label,
    string|MarkupInterface $description = '',
    array $filter = [],
    array $fields = [],
    int $limit = 1000,
    array $default_value = NULL,
    array $properties = [],
  ): array {
    $payload = [
      'fields' => [
        'include' => array_unique(array_merge(['name'], $fields)),
      ],
      'filter' => [
        'conditions' => [
          [
            'field' => 'id',
            'value' => [
              'from' => 0,
            ],
          ],
          $filter,
        ],
        'operator' => 'AND',
      ],
      'limit' => max($limit, 1000),
      'sort' => ['id:asc'],
    ];

    $options = [];
    $count = 0;
    $last_id = 0;
    $client = $this->getReliefWebApiClient();

    // @todo move the loop to the API client?
    while (TRUE) {
      $data = $client->request('GET', $resource, $payload);

      if (empty($data['data'])) {
        break;
      }

      $count += $data['count'];
      $total = $data['totalCount'];

      // @todo mmaybe add an option to the API client to return directly the
      // list of items as an associative array keyed by IDs with the fields as
      // values.
      foreach ($data['data'] as $item) {
        $last_id = (int) $item['id'];

        if (isset($item['fields']['name'])) {
          $item_fields = $item['fields'];
          $name = $item_fields['name'];

          // Add the extra fields to help selecting the item.
          $extra_fields = [];
          foreach ($fields as $key) {
            if (isset($item_fields[$key]) && $item_fields[$key] !== $name) {
              $extra_fields[$item_fields[$key]] = $item_fields[$key];
            }
          }
          if (!empty($extra_fields)) {
            $name .= ' (' . implode(', ', $extra_fields) . ')';
          }

          $options[$item['id']] = $name;
        }
      }

      if ($count < $total) {
        $payload['filter']['conditions'][0]['value']['from'] = $last_id + 1;
      }
      else {
        break;
      }
    }

    if (empty($options)) {
      // @todo this should not happen so maybe disable the form submission in
      // that case.
      return [];
    }

    // Sort by alpha ascending.
    LocalizationHelper::collatedAsort($options);

    // @todo when there are less than 10 options, maybe show checkboxes or
    // radios.
    return [
      '#type' => 'ocha_reliefweb_select',
      '#title' => $label,
      '#description' => $description,
      '#options' => $options,
      '#default_value' => $default_value,
      '#empty_option' => $this->t('- Select a value -'),
      '#empty_value' => '_none',
    ] + $properties;
  }

  /**
   * Convert a mixed value to a list of integers.
   *
   * @param mixed $value
   *   Value.
   *
   * @return array<int>
   *   List of integers.
   */
  public function toIntArray(mixed $value): array {
    $values = [];
    if (is_scalar($value)) {
      $values = [(int) $value];
    }
    elseif (is_array($value)) {
      $values = array_map(function ($item) {
        return (int) $item;
      }, $value);
    }
    return array_filter(array_values($values));
  }

  /**
   * Convert a date array/object to ISO 8601 date string.
   *
   * @param mixed $date
   *   The date data.
   *
   * @return string
   *   An ISO 8601 date or empty string if the conversion could not be done.
   */
  public function toIsoDate(mixed $date): string {
    if ($date instanceof DrupalDateTime) {
      return $date->format('c');
    }
    return '';
  }

  /**
   * Splits a string to an array of strings using the given delimiter.
   *
   * @param mixed $string
   *   The input string.
   * @param string $regex
   *   The regex to use to split the string.
   *
   * @return array<string>
   *   The list of strings.
   */
  public function toStringArray(mixed $string, string $regex = '#\s*[\n, ]\s*#'): array {
    if (is_string($string)) {
      return array_filter(preg_split($regex, trim($string)));
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_entity_type_id = $field_definition->getTargetEntityTypeId();
    return $target_entity_type_id === 'reliefweb_resource';
  }

}
