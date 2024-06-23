<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates ReliefWeb Resource related local actions.
 */
class ReliefWebResourceDynamicLocalActions extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    TranslationInterface $string_translation,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $bundle_entities = $this->entityTypeManager
      ->getStorage('reliefweb_resource_type')
      ->loadMultiple();

    foreach ($bundle_entities ?? [] as $bundle => $bundle_entity) {
      $this->derivatives['ocha_reliefweb.reliefweb_resource.add_' . $bundle] = [
        'route_name' => 'entity.reliefweb_resource.add_form',
        'route_parameters' => [
          'reliefweb_resource_type' => $bundle,
        ],
        'title' => $this->t('Add @title', [
          '@title' => $bundle_entity->label(),
        ]),
        'appears_on' => ['entity.reliefweb_resource.collection'],
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
