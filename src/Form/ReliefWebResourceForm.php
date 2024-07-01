<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ocha_reliefweb\Services\ReliefWebApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base ReliefWeb Resource entity form.
 */
class ReliefWebResourceForm extends ContentEntityForm {

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\ocha_reliefweb\Services\ReliefWebApiClient $apiClient
   *   The ReliefWeb API client.
   */
  public function __construct(
    EntityRepositoryInterface $entityRepository,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    TimeInterface $time,
    protected ReliefWebApiClient $apiClient,
  ) {
    parent::__construct($entityRepository, $entityTypeBundleInfo, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('ocha_reliefweb.api.client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $this->setFormTitle($form, $form_state);
    return $form;
  }

  /**
   * Set the form title.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function setFormTitle(array &$form, FormStateInterface $form_state): void {
    switch ($form_state->getFormObject()->getOperation()) {
      case 'default':
      case 'add':
        $form['#title'] = $this->t('Add @bundle_label', [
          '@bundle_label' => $this->getEntity()->bundle->entity->label(),
        ]);
        break;

      case 'edit':
        $form['#title'] = $this->t('Edit @bundle_label %entity_label', [
          '@bundle_label' => $this->getEntity()->bundle->entity->label(),
          '%entity_label' => $this->getEntity()->label(),
        ]);
        break;

      case 'delete':
        $form['#title'] = $this->t('Delete @bundle_label %entity_label', [
          '@bundle_label' => $this->getEntity()->bundle->entity->label(),
          '%entity_label' => $this->getEntity()->label(),
        ]);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Set a flag so the content is submitted.
    $this->getEntity()->setSubmitContent(TRUE);

    // Save the entity.
    parent::save($form, $form_state);

    // Redirect to the collection route after saving.
    $entity_type_id = $this->getEntity()->getEntityTypeId();
    $form_state->setRedirect("entity.{$entity_type_id}.collection");
  }

}
