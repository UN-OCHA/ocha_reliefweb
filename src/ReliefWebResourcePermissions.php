<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ocha_reliefweb\Entity\ReliefWebResourceType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for ReliefWeb Resource entity bundles.
 */
class ReliefWebResourcePermissions implements ContainerInjectionInterface {

  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Returns an array of ReliefWeb Resource type permissions.
   *
   * @return array
   *   The ReliefWeb Resource type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function reliefwebResourceTypePermissions(): array {
    $types = $this->entityTypeManager
      ->getStorage('reliefweb_resource_type')
      ->loadMultiple();

    return $this->generatePermissions($types, [
      $this,
      'buildPermissions',
    ]);
  }

  /**
   * Returns a list of ReliefWeb Resource permissions for a given type.
   *
   * @param \Drupal\ocha_reliefweb\Entity\ReliefWebResourceType $type
   *   The ReliefWeb Resource type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ReliefWebResourceType $type): array {
    $type_id = $type->id();
    $type_params = [
      '%type_name' => $type->label(),
    ];

    // @todo add permissions for "groups"?
    return [
      "create {$type_id} content" => [
        'title' => $this->t('%type_name: Create new content', $type_params),
      ],
      "edit own {$type_id} content" => [
        'title' => $this->t('%type_name: Edit own content', $type_params),
        'description' => $this->t('Note that anonymous users with this permission are able to edit any content created by any anonymous user.'),
      ],
      "edit any {$type_id} content" => [
        'title' => $this->t('%type_name: Edit any content', $type_params),
      ],
      "delete own {$type_id} content" => [
        'title' => $this->t('%type_name: Delete own content', $type_params),
        'description' => $this->t('Note that anonymous users with this permission are able to delete any content created by any anonymous user.'),
      ],
      "delete any {$type_id} content" => [
        'title' => $this->t('%type_name: Delete any content', $type_params),
      ],
    ];
  }

}
