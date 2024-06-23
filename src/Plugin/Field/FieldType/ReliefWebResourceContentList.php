<?php

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldType;

use Drupal\Core\Field\MapFieldItemList;

/**
 * List of ReliefWebFile field items.
 */
class ReliefWebResourceContentList extends MapFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($updated) {
    $updated = parent::postSave($updated);
    // @todo save files as permanent?
    return $updated;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // @todo delete files?
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (is_array($values) && !empty($values)) {
      if (!is_numeric(array_key_first($values))) {
        $values = [$values];
      }
      foreach ($values as $delta => $item_values) {
        if (!isset($item_values['value'])) {
          $values[$delta] = ['value' => $item_values];
        }
      }
    }
    parent::setValue($values, $notify);
  }

}
