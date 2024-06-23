<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\ocha_reliefweb\Services\ReliefWebApiClient;

/**
 * Trait for classes that want to access the ReliefWeb API client.
 */
trait ReliefWebApiClientTrait {

  /**
   * The ReliefWeb API client.
   *
   * @var ?\Drupal\ocha_reliefweb\Services\ReliefWebApiClient
   */
  protected ReliefWebApiClient $reliefwebApiClient;

  /**
   * Get the ReliefWeb API Client.
   *
   * @return \Drupal\ocha_reliefweb\Services\ReliefWebApiClient
   *   The ReliefWeb API client.
   */
  public function getReliefWebApiClient(): ReliefWebApiClient {
    if (!isset($this->reliefwebApiClient)) {
      $this->reliefwebApiClient = \Drupal::service('ocha_reliefweb.api.client');
    }
    return $this->reliefwebApiClient;
  }

}
