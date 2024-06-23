<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * The 'reliefweb_resource_content_report_formatter' formatter plugin.
 *
 * @FieldFormatter(
 *   id = "reliefweb_resource_content_report_formatter",
 *   label = @Translation("ReliefWeb Resource Content Report Formatter"),
 *   field_types = {
 *     "reliefweb_resource_content"
 *   }
 * )
 */
class ReliefWebResourceContentReportFormatter extends ReliefWebResourceContentDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // @todo populate that.
      $element = [];

      $elements[$delta] = $element;
    }

    return $elements;
  }

}
