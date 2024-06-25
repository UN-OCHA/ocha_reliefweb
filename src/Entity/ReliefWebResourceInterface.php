<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\ocha_reliefweb\ReliefWebApiClientInterface;
use Drupal\ocha_reliefweb\ReliefWebBundleEntityInterface;
use Drupal\ocha_reliefweb\ReliefWebConfigInterface;
use Drupal\ocha_reliefweb\ReliefWebEntityRepositoryInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for a ReliefWeb Resource entity.
 */
interface ReliefWebResourceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, ReliefWebBundleEntityInterface, ReliefWebApiClientInterface, ReliefWebConfigInterface, ReliefWebEntityRepositoryInterface {

  /**
   * Get the API resource for the form.
   *
   * @return string
   *   ReliefWeb API resource.
   */
  public function getApiResource(): string;

  /**
   * Get the UUID of the resource in the API.
   *
   * If not set, this is computed from the resource URL on the current site
   * and the DNS namespaced UUID V5 of reliefweb.int.
   *
   * @return string
   *   UUID.
   */
  public function getResourceUuid(): string;

  /**
   * Get the URL of the resource on the current site.
   *
   * @return string
   *   URL.
   */
  public function getResourceUrl(): string;

  /**
   * Get the content of the resource.
   *
   * If this is a pending submission, we use the stored submitted content.
   * Otherwise we retrieve the content from the API.
   *
   * @return array
   *   The resource's content.
   *
   * @todo check how to handle content from partial submissions.
   * @todo expand the information on the expected output.
   */
  public function getContent(): array;

  /**
   * Get the creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity.
   */
  public function getCreatedTime(): int;

  /**
   * Set the creation timestamp.
   *
   * @param int $timestamp
   *   The creation timestamp of the entity.
   *
   * @return $this
   *   The called entity.
   */
  public function setCreatedTime(int $timestamp): static;

}
