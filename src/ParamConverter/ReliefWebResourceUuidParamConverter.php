<?php

namespace Drupal\ocha_reliefweb\ParamConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\ocha_reliefweb\Entity\ReliefWebResourceInterface;
use Drupal\ocha_reliefweb\Helpers\UuidHelper;
use Symfony\Component\Routing\Route;

/**
 * Convert a ReliefWeb Resource UUID into the matching entity..
 */
class ReliefWebResourceUuidParamConverter implements ParamConverterInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

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
  public function convert($value, $definition, $name, array $defaults) {
    if (!UuidHelper::isUuidValid($value)) {
      return NULL;
    }

    $entities = $this
      ->getEntityTypeManager()
      ->getStorage('reliefweb_resource')
      ->loadByProperties([
        'resource' => $value,
      ]);

    $entity = reset($entities);

    return $entity instanceof ReliefWebResourceInterface ? $entity : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'reliefweb_resource_uuid';
  }

}
