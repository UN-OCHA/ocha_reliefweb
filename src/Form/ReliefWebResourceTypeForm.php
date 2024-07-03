<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for ReliefWeb Resource type forms.
 */
class ReliefWebResourceTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Settings',
      '#tree' => TRUE,
    ];

    $form['settings']['disable_page_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable page title'),
      '#description' => $this->t('Disable the page title block and use the title from the resource content instead.'),
      '#default_value' => $this->getEntity()->isPageTitleDisabled(),
    ];

    $form['settings']['preview_warning'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Preview warning'),
      '#description' => $this->t('Warning message when viewing the preview of an unpublished submission.'),
      '#default_value' => $this->getEntity()->getPreviewWarning(),
      '#format' => 'ocha_reliefweb_editor',
      '#allowed_formats' => ['ocha_reliefweb_editor'],
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->set('settings', $form_state->getValue('settings'));

    $status = $entity->save();
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The @id resource type has been updated.', [
        '@id' => $entity->id(),
      ]));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The @id resource type has been created.', [
        '@id' => $entity->id(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
