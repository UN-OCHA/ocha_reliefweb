<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates ReliefWeb Resource related local tasks.
 */
class ReliefWebResourceDynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Creates a DynamicLocalTasks object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives['entity.reliefweb_resource.canonical'] = [
      'route_name' => 'entity.reliefweb_resource.canonical',
      'title' => $this->t('View'),
      'base_route' => 'entity.reliefweb_resource.canonical',
      'weight' => 1,
    ] + $base_plugin_definition;

    $this->derivatives['entity.reliefweb_resource.edit_form'] = [
      'route_name' => 'entity.reliefweb_resource.edit_form',
      'title' => $this->t('Edit'),
      'base_route' => 'entity.reliefweb_resource.canonical',
      'weight' => 2,
    ] + $base_plugin_definition;

    $this->derivatives['entity.reliefweb_resource.delete_form'] = [
      'route_name' => 'entity.reliefweb_resource.delete_form',
      'title' => $this->t('Delete'),
      'base_route' => 'entity.reliefweb_resource.canonical',
      'weight' => 10,
    ] + $base_plugin_definition;

    return $this->derivatives;
  }

}
