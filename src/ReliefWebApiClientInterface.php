<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb;

use Drupal\ocha_reliefweb\Services\ReliefWebApiClient;

/**
 * Interface for classes that want to access the ReliefWeb API client.
 */
interface ReliefWebApiClientInterface {

  /**
   * Get the ReliefWeb API Client.
   *
   * @return \Drupal\ocha_reliefweb\Services\ReliefWebApiClient
   *   The ReliefWeb API client.
   */
  public function getReliefWebApiClient(): ReliefWebApiClient;

}
