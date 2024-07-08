<?php

namespace Drupal\ocha_reliefweb\Element;

use Drupal\Core\Render\Element\Select;

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
 * @FormElement("ocha_reliefweb_select")
 */
class OchaReliefWebSelect extends Select {

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
