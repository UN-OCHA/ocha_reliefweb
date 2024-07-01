<?php

namespace Drupal\ocha_reliefweb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the ReliefWeb Resource type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "reliefweb_resource_type",
 *   label = @Translation("ReliefWeb Resource type"),
 *   label_collection = @Translation("ReliefWeb Resource types"),
 *   label_singular = @Translation("ReliefWeb Resource type"),
 *   label_plural = @Translation("ReliefWeb Resource types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ReliefWeb Resource type",
 *     plural = "@count ReliefWeb Resource types"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\ocha_reliefweb\ReliefWebResourceTypeAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\ocha_reliefweb\Form\ReliefWebResourceTypeForm",
 *       "edit" = "Drupal\ocha_reliefweb\Form\ReliefWebResourceTypeForm",
 *     },
 *     "list_builder" = "Drupal\ocha_reliefweb\ReliefWebResourceTypeListBuilder",
 *     "form_builder" = "Drupal\ocha_reliefweb\ReliefWebResourceTypeFormBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "permissions" = "Drupal\user\Entity\EntityPermissionsRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer reliefweb resource types",
 *   config_prefix = "reliefweb_resource_type",
 *   bundle_of = "reliefweb_resource",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "description",
 *     "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/reliefweb_resource_type/{reliefweb_resource_type}/edit",
 *     "entity-permissions-form" = "/admin/structure/reliefweb_resource_type/{reliefweb_resource_type}/permissions",
 *     "collection" = "/admin/structure/reliefweb_resource_type"
 *   },
 *   constraints = {
 *     "ImmutableProperties" = {"id"},
 *   }
 * )
 */
class ReliefWebResourceType extends ConfigEntityBundleBase implements ReliefWebResourceTypeInterface {

  /**
   * The ReliefWeb Resource type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The ReliefWeb Resource type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this ReliefWeb resource type.
   *
   * @var string
   */
  protected $description;

  /**
   * The bundle settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function isPageTitleDisabled(): bool {
    return $this->settings['disable_page_title'] ?? TRUE;
  }

}
