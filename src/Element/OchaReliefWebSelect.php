<?php

namespace Drupal\ocha_reliefweb\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an ocha_reliefweb_select form element.
 *
 * Properties:
 * - #cardinality: (optional) How many options can be selected. Default is
 *   unlimited.
 *
 * Simple usage example:
 * @code
 *   $form['example_select'] = [
 *     '#type' => 'ocha_reliefweb_select',
 *     '#title' => $this->t('Select element'),
 *     '#options' => [
 *       '1' => $this->t('One'),
 *       '2' => [
 *         '2.1' => $this->t('Two point one'),
 *         '2.2' => $this->t('Two point two'),
 *       ],
 *       '3' => $this->t('Three'),
 *     ],
 *   ];
 *
 * The render element sets a bunch of default values to configure the
 * ocha_reliefweb_select element. Nevertheless all ocha_reliefweb_select config
 * values can be overwritten with the '#ocha_reliefweb_select' property.
 * @code
 *   $form['my_element'] = [
 *     '#type' => 'ocha_reliefweb_select',
 *     '#ocha_reliefweb_select' => [
 *       'show-all' => TRUE,
 *     ],
 *   ];
 *
 * @FormElement("ocha_reliefweb_select")
 */
class OchaReliefWebSelect extends Select {

  // /**
  //  * {@inheritdoc}
  //  */
  // public function getInfo() {
  //   $info = parent::getInfo();
  //   $class = get_class($this);

  //   // Apply default form element properties.
  //   $info['#empty_value'] = '';
  //   $info['#cardinality'] = 0;
  //   $info['#pre_render'][] = [$class, 'preRenderOverwrites'];
  //   $info['#ocha_reliefweb_select'] = [];

  //   return $info;
  // }

  // /**
  //  * {@inheritdoc}
  //  */
  // public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
  //   // Potentially the #value is set directly, so it contains the 'target_id'
  //   // array structure instead of a string.
  //   if ($input !== FALSE && is_array($input)) {
  //     $input = array_map(function ($item) {
  //       return isset($item['target_id']) ? $item['target_id'] : $item;
  //     }, $input);
  //     return array_combine($input, $input);
  //   }

  //   return parent::valueCallback($element, $input, $form_state);
  // }

  // /**
  //  * {@inheritdoc}
  //  */
  // public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
  //   if (!$element['#multiple'] && !isset($element['#options'][$element['#empty_value']])) {
  //     $empty_option = [$element['#empty_value'] => ''];
  //     $element['#options'] = $empty_option + $element['#options'];
  //   }

  //   // Set the type from ocha_reliefweb_select to select to get proper form validation.
  //   $element['#type'] = 'select';

  //   return $element;
  // }

  // /**
  //  * {@inheritdoc}
  //  */
  // public static function preRenderSelect($element) {
  //   $element = parent::preRenderSelect($element);
  //   $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
  //   $multiple = $element['#multiple'];

  //   if ($multiple) {
  //     $element['#attributes']['multiple'] = 'multiple';
  //     $element['#attributes']['name'] = $element['#name'] . '[]';
  //   }

  //   $current_language = \Drupal::languageManager()->getCurrentLanguage();

  //   // Placeholder should be taken from #placeholder property if it set.
  //   // Otherwise we can take it from '#empty_option' property.
  //   $placeholder_text = $required ? new TranslatableMarkup('- Select -') : new TranslatableMarkup('- None -');
  //   $placeholder = ['id' => '', 'text' => $placeholder_text];
  //   if (!empty($element['#empty_value'])) {
  //     $placeholder['id'] = $element['#empty_value'];
  //   }
  //   if (!empty($element['#placeholder'])) {
  //     $placeholder['text'] = $element['#placeholder'];
  //   }
  //   elseif (!empty($element['#empty_option'])) {
  //     $placeholder['text'] = $element['#empty_option'];
  //   }

  //   // Defining the ocha_reliefweb_select configuration.
  //   $settings = [
  //     'multiple' => $multiple,
  //     'placeholder' => $placeholder,
  //     'dir' => $current_language->getDirection(),
  //     'language' => $current_language->getId(),
  //     'width' => '100%',
  //   ];

  //   $element['#attributes']['class'][] = 'select-a11y-widget';
  //   $element['#attributes']['data-select-a11y-config'] = $settings;

  //   // Adding the ocha_reliefweb_select library.
  //   $element['#attached']['library'][] = 'ocha_reliefweb_select/ocha_reliefweb_select.widget';

  //   return $element;
  // }

  // /**
  //  * Allows to modify the ocha_reliefweb_select settings.
  //  */
  // public static function preRenderOverwrites($element) {
  //   if (!$element['#multiple']) {
  //     $empty_option = [$element['#empty_value'] => ''];
  //     $element['#options'] = $empty_option + $element['#options'];
  //   }

  //   // Allow to overwrite the default settings and set additional settings.
  //   foreach ($element["#ocha_reliefweb_select"] as $key => $value) {
  //     $element['#attributes']['data-select-a11y-config'][$key] = $value;
  //   }
  //   $element['#attributes']['data-select-a11y-config'] = Json::encode($element['#attributes']['data-select-a11y-config']);

  //   return $element;
  // }

  /**
   * {@inheritdoc}
   */
  public static function preRenderSelect($element) {
    $element = parent::preRenderSelect($element);

    // Add the attribute to transform the select element.
    $element['#attributes']['data-ocha-reliefweb-select'] = '';

    // Adding the ocha_reliefweb_select library.
    $element['#attached']['library'][] = 'ocha_reliefweb/ocha-reliefweb-select';

    return $element;
  }

}
