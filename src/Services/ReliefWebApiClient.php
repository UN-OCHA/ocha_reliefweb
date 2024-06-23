<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ReliefWeb API client service class.
 */
class ReliefWebApiClient {

  /**
   * The base API URL.
   *
   * @var string
   */
  protected string $apiUrl;

  /**
   * The appname parameter to pass to the API request.
   *
   * @var string
   */
  protected string $appname;

  /**
   * Flag to indicate if the API request results should be cached.
   *
   * @var bool
   */
  protected bool $cacheEnabled;

  /**
   * The cache lifetime in seconds.
   *
   * @var int
   */
  protected int $cacheLifetime;

  /**
   * The cache namespace for the cache Ids and tags.
   *
   * @var string
   */
  protected string $cacheNamespace;

  /**
   * Map API resources to cache tags.
   *
   * @var array
   */
  protected array $cacheTags = [];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected CacheBackendInterface $cacheBackend,
    protected ConfigFactoryInterface $configFactory,
    protected TimeInterface $time,
    protected ClientInterface $httpClient,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected RequestStack $requestStack,
  ) {
  }

  /**
   * Get the module configuration.
   *
   * @param string $name
   *   Config name.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The module configuration.
   */
  protected function config(string $name = 'ocha_reliefweb.settings'): ImmutableConfig {
    return $this->configFactory->get($name);
  }

  /**
   * Get the module logger.
   *
   * @param string $channel
   *   Logger channel. Defaults to `ocha_reliefweb`.
   *
   * @return \Psr\Log\LoggerInterface
   *   Logger.
   */
  protected function getLogger(string $channel = 'ocha_reliefweb'): LoggerInterface {
    return $this->loggerFactory->get($channel);
  }

  /**
   * Perform a POST request against the ReliefWeb API.
   *
   * @param string $method
   *   The method (GET or POST) to use for the request.
   * @param string $resource
   *   API resource endpoint (ex: reports).
   * @param array $payload
   *   API request payload (with fields, filters, sort etc.)
   * @param array $headers
   *   Extra request headers.
   * @param bool $decode
   *   Whether to decode (json) the output or not.
   * @param int $timeout
   *   Request timeout.
   * @param bool $cache_enabled
   *   Whether to cache the queries or not.
   *
   * @return array|string|null
   *   The data from the API response or NULL in case of error.
   */
  public function request(
    string $method,
    string $resource,
    array $payload,
    array $headers = [],
    bool $decode = TRUE,
    int $timeout = 5,
    bool $cache_enabled = TRUE,
  ): array|string|null {
    $queries = [
      $resource => [
        'method' => $method,
        'resource' => $resource,
        'payload' => $payload,
        'headers' => $headers,
      ],
    ];

    $results = $this->requestMultiple($queries, $decode, $timeout, $cache_enabled);
    return $results[$resource] ?? NULL;
  }

  /**
   * Perform parallel queries to the API.
   *
   * @param array $queries
   *   List of queries to perform in parallel. Each item is an associative
   *   array with the resource and the query payload.
   * @param bool $decode
   *   Whether to decode (json) the output or not.
   * @param int $timeout
   *   Request timeout.
   * @param bool $cache_enabled
   *   Whether to cache the queries or not.
   *
   * @return array
   *   Return array where each item contains the response to the corresponding
   *   query to the API.
   *
   * @see https://docs.guzzlephp.org/en/stable/quickstart.html#concurrent-requests
   */
  public function requestMultiple(
    array $queries,
    bool $decode = TRUE,
    int $timeout = 5,
    bool $cache_enabled = TRUE,
  ): array {
    $results = [];
    $api_url = $this->getApiUrl();
    $appname = $this->getAppName();
    $cache_enabled = $cache_enabled && $this->isCacheEnabled();

    // Initialize the result array and retrieve the data for the cached queries.
    $cache_ids = [];
    foreach ($queries as $index => $query) {
      $method = $query['method'] ?? 'POST';
      $payload = $query['payload'] ?? [];

      // Sanitize the query payload.
      if (is_array($payload)) {
        $payload = $this->sanitizePayload($payload);
      }

      // Update the query payload.
      $queries[$index]['payload'] = $payload;

      // Attempt to get the data from the cache.
      $results[$index] = NULL;
      if ($cache_enabled) {
        // Retrieve the cache id for the query.
        $cache_id = $this->getCacheIdFromPayload($query['resource'], $method, $payload);
        $cache_ids[$index] = $cache_id;
        // Attempt to retrieve the cached data for the query.
        $cache = $this->cacheBackend->get($cache_id);
        if (isset($cache->data)) {
          $results[$index] = $cache->data;
        }
      }
    }

    // Prepare the requests.
    $promises = [];
    foreach ($queries as $index => $query) {
      // Skip queries with cached data.
      if (isset($results[$index])) {
        continue;
      }

      $method = $query['method'] ?? 'POST';
      $payload = $query['payload'] ?? [];

      $parameters = [
        'appname' => $appname,
      ];

      if ($method === 'GET') {
        // If the payload is a string, we assume, it is a query string.
        if (!is_array($payload)) {
          parse_str($payload, $parameters);
        }
        else {
          $parameters += $payload;
        }
      }
      else {
        // Encode the payload if it's not already.
        if (is_array($payload)) {
          $payload = json_encode($payload);

          // Skip the request if something is wrong with the payload.
          if ($payload === FALSE) {
            $results[$index] = NULL;
            $this->getLogger()->error('Could not encode payload when requesting @url: @payload', [
              '@url' => $api_url . '/' . $query['resource'],
              '@payload' => strtr(print_r($query['payload'], TRUE), "\n", " "),
            ]);
            continue;
          }
        }
      }

      $url = $api_url . '/' . $query['resource'] . '?' . http_build_query($parameters);
      $headers = $query['headers'] ?? [];

      try {
        $options = [
          'timeout' => $timeout,
          'connect_timeout' => $timeout,
        ];
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
          $options['headers'] = ['Content-Type' => 'application/json'] + $headers;
          $options['body'] = $payload;
        }
        $promises[$index] = $this->httpClient->requestAsync($method, $url, $options);
      }
      catch (\Exception $exception) {
        $this->getLogger()->error('Exception while querying @url: @exception', [
          '@url' => $api_url . '/' . $query['resource'],
          '@exception' => $exception->getMessage(),
        ]);
      }
    }

    // Execute the requests in parallel and retrieve and cache the response's
    // data.
    $promise_results = Utils::settle($promises)->wait();
    foreach ($promise_results as $index => $result) {
      $data = NULL;

      // Parse the response in case of success.
      if ($result['state'] === 'fulfilled') {
        $response = $result['value'];

        // Retrieve the raw response's data.
        if ($response->getStatusCode() === 200) {
          $data = (string) $response->getBody();
        }
        else {
          $this->getLogger()->notice('Unable to retrieve API data (code: @code) when requesting @url with payload @payload', [
            '@code' => $response->getStatusCode(),
            '@url' => $api_url . '/' . $queries[$index]['resource'],
            '@payload' => strtr(print_r($queries[$index]['payload'], TRUE), "\n", " "),
          ]);
          $data = '';
        }
      }
      // Otherwise log the error.
      else {
        $this->getLogger()->notice('Unable to retrieve API data (code: @code) when requesting @url with payload @payload: @reason', [
          '@code' => $result['reason']->getCode(),
          '@url' => $api_url . '/' . $queries[$index]['resource'],
          '@payload' => strtr(print_r($queries[$index]['payload'], TRUE), "\n", " "),
          '@reason' => $result['reason']->getMessage(),
        ]);
      }

      // Cache the data unless cache is disabled or there was an issue with the
      // request in which case $data is NULL.
      if (isset($cache, $cache_ids[$index], $queries[$index]['resource'])) {
        $tags = $this->getCacheTags($queries[$index]['resource']);
        $this->cacheBackend->set($cache_ids[$index], $data, $this->getCacheExpiration(), $tags);
      }

      $results[$index] = $data;
    }

    // We don't store the decoded data. This is to ensure that we can use the
    // same cached data regardless of whether to return JSON data or not.
    if ($decode) {
      foreach ($results as $index => $data) {
        if (!empty($data)) {
          // Decode the data, skip if invalid.
          try {
            $data = json_decode($data, TRUE, 512, JSON_THROW_ON_ERROR);
          }
          catch (\Exception $exception) {
            $data = NULL;
            $this->getLogger()->notice('Unable to decode ReliefWeb API data for request @url with payload @payload', [
              '@url' => $api_url . '/' . $queries[$index]['resource'],
              '@payload' => strtr(print_r($queries[$index]['payload'], TRUE), "\n", " "),
            ]);
          }

          // Add the resulting data with same index as the query.
          $results[$index] = $data;
        }
      }
    }
    return $results;
  }

  /**
   * Submit content.
   *
   * @param string $resource
   *   API resource.
   * @param array $payload
   *   Content to submit.
   * @param array $headers
   *   Request headers. This notably must include the X-RW-POST-API-KEY and
   *   X-RW-POST-API-PROVIDER headers.
   * @param int $timeout
   *   Request timeout.
   *
   * @return mixed
   *   The response's data.
   *
   * @throws \Exception
   *   An exception if the request was not successful.
   *
   * @todo review the return value.
   */
  public function submitContent(
    string $resource,
    array $payload,
    array $headers,
    int $timeout = 5,
  ): mixed {
    $api_url = $this->getApiUrl();
    $appname = $this->getAppName();

    $url = rtrim($api_url) . '/' . ltrim($resource, '/');
    $url .= '?' . http_build_query(['appname' => $appname]);

    if (!isset($headers['X-RW-POST-API-KEY'])) {
      $this->getLogger()->error('Exception while submitting content for @url: @exception', [
        '@url' => $url,
        '@exception' => 'Missing API key.',
      ]);
      // @todo review this message.
      throw new \Exception('Missing API key.');
    }

    if (!isset($headers['X-RW-POST-API-PROVIDER'])) {
      $this->getLogger()->error('Exception while submitting content for @url: @exception', [
        '@url' => $url,
        '@exception' => 'Missing API provider.',
      ]);
      // @todo review this message.
      throw new \Exception('Missing API provider.');
    }

    $options = [
      'timeout' => $timeout,
      'connect_timeout' => $timeout,
      'headers' => ['Content-Type' => 'application/json'] + $headers,
      'body' => json_encode($payload),
    ];

    try {
      $response = $this->httpClient->request('PUT', $url, options: $options);
    }
    catch (\Exception $exception) {
      $this->getLogger()->error('Exception while submitting content for @url: @exception', [
        '@url' => $url,
        '@exception' => $exception->getMessage(),
      ]);
      // @todo review this message, for example, check the type of exception
      // (timeout etc.).
      throw new \Exception('Exception while submitting content.');
    }

    // Retrieve the raw response's data.
    if ($response->getStatusCode() === 200) {
      $data = (string) $response->getBody();
    }
    else {
      $error = (string) $response->getBody();

      $this->getLogger()->error('Error while submitting content for @url (code @code): @error.', [
        '@url' => $url,
        '@code' => $response->getStatusCode(),
        '@error' => $error,
      ]);
      throw new \Exception(strtr('@code @error.', [
        '@code' => $response->getStatusCode(),
        '@error' => $error,
      ]));
    }

    // Decode the data, skip if invalid.
    try {
      $decoded = json_decode($data, TRUE, 512, \JSON_THROW_ON_ERROR);
    }
    catch (\Exception $exception) {
      $this->getLogger()->error('Unable to decode POST API JSON schema for request @url.', [
        '@url' => $url,
      ]);
      throw new \Exception('Invalid response data.');
    }

    return $decoded;
  }

  /**
   * Retrieve a POST API JSON schema for a resource type.
   *
   * @param string $type
   *   Resource type.
   *
   * @return array|null
   *   Associative array with the `raw` JSON Schema string and the `decoded`
   *   version. NULL if the schema could not be retrieved or decoded.
   */
  public function getPostApiJsonSchema(string $type): ?array {
    $cache_id = $this->getCacheNamespace() . 'post_api:schema:' . $type;
    $cache = $this->cacheBackend->get($cache_id);

    if (!isset($cache->data)) {
      $url = $this->config()->get('reliefweb_api_post_api_schema_url');
      if (empty($url)) {
        throw new \Exception('Missing or invalid ReliefWeb POST API schema URL');
      }
      $url = rtrim($url, '/') . '/' . $type . '.json';
      $timeout = 5;

      try {
        $response = $this->httpClient->get($url, options: [
          'timeout' => $timeout,
          'connect_timeout' => $timeout,
        ]);
      }
      catch (\Exception $exception) {
        $this->getLogger()->error('Exception while querying @url: @exception', [
          '@url' => $url,
          '@exception' => $exception->getMessage(),
        ]);
        return NULL;
      }

      // Retrieve the raw response's data.
      if ($response->getStatusCode() === 200) {
        $data = (string) $response->getBody();
      }
      else {
        $this->getLogger()->error('Unable to retrieve POST API JSON schema (code: @code) for request @url.', [
          '@code' => $response->getStatusCode(),
          '@url' => $url,
        ]);
        return NULL;
      }

      // Decode the data, skip if invalid.
      try {
        $decoded = json_decode($data, TRUE, 512, \JSON_THROW_ON_ERROR);
      }
      catch (\Exception $exception) {
        $this->getLogger()->error('Unable to decode POST API JSON schema for request @url.', [
          '@url' => $url,
        ]);
        return NULL;
      }

      $schema = [
        'raw' => $data,
        'decoded' => $decoded,
      ];

      $this->cacheBackend->set($cache_id, $schema);
      return $schema;
    }

    return $cache->data;
  }

  /**
   * Sanitize and simplify an API query payload.
   *
   * @param array $payload
   *   API query payload.
   * @param bool $combine
   *   TRUE to optimize the filters by combining their values when possible.
   *
   * @return array
   *   Sanitized payload.
   */
  public function sanitizePayload(array $payload, bool $combine = FALSE): array {
    if (empty($payload)) {
      return [];
    }
    // Remove search value and fields if the value is empty.
    if (empty($payload['query']['value'])) {
      unset($payload['query']);
    }
    // Optimize the filter if any.
    if (isset($payload['filter'])) {
      $filter = $this->optimizeFilter($payload['filter'], $combine);

      if (!empty($filter)) {
        $payload['filter'] = $filter;
      }
      else {
        unset($payload['filter']);
      }
    }
    // Optimize the facet filters if any.
    if (isset($payload['facets'])) {
      foreach ($payload['facets'] as $key => $facet) {
        if (isset($facet['filter'])) {
          $filter = $this->optimizeFilter($facet['filter'], $combine);
          if (!empty($filter)) {
            $payload['facets'][$key]['filter'] = $filter;
          }
          else {
            unset($payload['facets'][$key]['filter']);
          }
        }
      }
    }
    return $payload;
  }

  /**
   * Optimize a filter, removing uncessary nested conditions.
   *
   * @param array $filter
   *   Filter following the API syntax.
   * @param bool $combine
   *   TRUE to optimize even more the filter by combining values when possible.
   *
   * @return array|null
   *   Optimized filter.
   */
  public function optimizeFilter(array $filter, bool $combine = FALSE): ?array {
    if (isset($filter['conditions'])) {
      if (isset($filter['operator'])) {
        $filter['operator'] = strtoupper($filter['operator']);
      }

      foreach ($filter['conditions'] as $key => $condition) {
        $condition = $this->optimizeFilter($condition, $combine);
        if (isset($condition)) {
          $filter['conditions'][$key] = $condition;
        }
        else {
          unset($filter['conditions'][$key]);
        }
      }
      // @todo eventually check if it's worthy to optimize by combining
      // filters with same field and same negation inside a conditional filter.
      if (!empty($filter['conditions'])) {
        if ($combine) {
          $filter['conditions'] = $this->combineConditions($filter['conditions'], $filter['operator'] ?? NULL);
        }
        if (count($filter['conditions']) === 1) {
          $condition = reset($filter['conditions']);
          if (!empty($filter['negate'])) {
            $condition['negate'] = TRUE;
          }
          $filter = $condition;
        }
      }
      else {
        $filter = NULL;
      }
    }
    return !empty($filter) ? $filter : NULL;
  }

  /**
   * Combine simple filter conditions to shorten the filters.
   *
   * @param array $conditions
   *   Filter conditions.
   * @param string $operator
   *   Operator to join the conditions.
   *
   * @return array
   *   Combined and simplied filter conditions.
   */
  public function combineConditions(array $conditions, string $operator = 'AND'): array {
    $operator = $operator ?: 'AND';
    $filters = [];
    $result = [];

    foreach ($conditions as $condition) {
      $field = $condition['field'] ?? NULL;
      $value = $condition['value'] ?? NULL;
      $condition_operator = $condition['operator'] ?? NULL;

      // Nested conditions - flatten the condition's conditions.
      if (!empty($condition['conditions'])) {
        $condition['conditions'] = $this->combineConditions($condition['conditions'], $condition_operator);
        $result[] = $condition;
      }
      // Existence filter - keep as is.
      elseif (is_null($value)) {
        $result[] = $condition;
      }
      // Range filter - keep as is.
      elseif (is_array($value) && (isset($value['from']) || isset($value['to']))) {
        $result[] = $condition;
      }
      // Different operator or negated condition - keep as is.
      elseif ((isset($condition_operator) && $condition_operator !== $operator) || !empty($condition['negate'])) {
        $result[] = $condition;
      }
      elseif (is_array($value)) {
        foreach ($value as $item) {
          $filters[$field][] = $item;
        }
      }
      else {
        $filters[$field][] = $value;
      }
    }

    foreach ($filters as $field => $values) {
      $filter = [
        'field' => $field,
      ];

      $value = array_unique($values);
      if (count($value) === 1) {
        $filter['value'] = reset($value);
      }
      else {
        $filter['value'] = $value;
        $filter['operator'] = $operator;
      }
      $result[] = $filter;
    }
    return $result;
  }

  /**
   * Get the ReliefWeb API URL.
   *
   * @return string
   *   ReliefWeb API URL.
   */
  protected function getApiUrl(): string {
    if (!isset($this->apiUrl)) {
      $api_url = $this->config()->get('reliefweb_api_url');
      if (empty($api_url) && !is_string($api_url)) {
        throw new \Exception('Missing or invalid ReliefWeb API URL');
      }
      $this->apiUrl = rtrim($api_url, '/');
    }
    return $this->apiUrl;
  }

  /**
   * Get the appname parameter to use in the API queries.
   *
   * @return string
   *   Appname.
   */
  protected function getAppName(): string {
    if (!isset($this->appname)) {
      $this->appname = $this->config()->get('reliefweb_api_appname') ?:
                       $this->requestStack->getCurrentRequest()->getHttpHost();
    }
    return $this->appname;
  }

  /**
   * Get whether caching is enabled or not.
   *
   * @var bool
   *   TRUE if caching is enabled.
   */
  protected function isCacheEnabled(): bool {
    if (!isset($this->cacheEnabled)) {
      $this->cacheEnabled = $this->config()->get('reliefweb_api_cache_enabled');
    }
    return $this->cacheEnabled;
  }

  /**
   * Get the cache lifetime in seconds.
   *
   * @var int
   *   Cache lifetime.
   */
  protected function getCacheLifetime(): int {
    if (!isset($this->cacheLifetime)) {
      $this->cacheLifetime = $this->config()->get('reliefweb_api_cache_lifetime');
    }
    return $this->cacheLifetime;
  }

  /**
   * Get the cache expiration date.
   *
   * @var int
   *   Cache expiration.
   */
  protected function getCacheExpiration(): int {
    return $this->time->getRequestTime() + $this->getCacheLifetime();
  }

  /**
   * Determine the cache id of an API query.
   *
   * @param string $resource
   *   API resource.
   * @param string $method
   *   API request method.
   * @param array|string|null $payload
   *   API payload.
   *
   * @return string
   *   Cache id.
   */
  public function getCacheIdFromPayload(string $resource, string $method, array|string|null $payload): string {
    $hash = hash('sha256', serialize($payload ?? ''));
    return $this->getCacheNamespace() . ':queries:' . $resource . ':' . $method . ':' . $hash;
  }

  /**
   * Determine the cache tags of an API query's resource.
   *
   * @param string $resource
   *   API resource.
   *
   * @return array
   *   Cache tags.
   */
  public function getCacheTags(string $resource): array {
    // @todo review what tags would make sense.
    $tags = $this->cacheTags[$resource] ?? [];
    $tags[] = $this->getCacheNamespace() . ':' . $resource;
    return $tags;
  }

  /**
   * Get the namespace for the cache Ids.
   *
   * @return string
   *   Namespace.
   */
  public function getCacheNamespace(): string {
    if (!isset($this->cacheNamespace)) {
      $this->cacheNamespace = $this->config()->get('reliefweb_api_cache_namespace') ?: 'reliefweb:api';
    }
    return $this->cacheNamespace;
  }

  /**
   * Update the host of API URL fields recursively.
   *
   * Note: this mostly for development to convert the URLs from the API used
   * for dev (ex: stage) to URLs starting with `reliefweb.int`.
   *
   * @param array $data
   *   API data.
   * @param string $replacement
   *   Replacement host and scheme.
   * @param string $pattern
   *   Pattern to replace.
   * @param string $recursive
   *   TRUE to also check subfields.
   */
  public function updateApiUrls(
    array &$data,
    string $replacement = 'https://reliefweb.int/',
    string $pattern = '#https?://[^/]+/#',
    bool $recursive = TRUE,
  ): void {
    foreach ($data as $key => $item) {
      if (is_string($item) && strpos($key, 'url') === 0) {
        $data[$key] = preg_replace($pattern, $replacement, $item);
      }
      elseif (is_array($item) && $recursive) {
        $this->updateApiUrls($data[$key], $replacement, $pattern, $recursive);
      }
    }
  }

  /**
   * Get the ReliefWeb UUID namespace.
   *
   * @return string
   *   UUID to use as namespace to generate V5 UUIDs.
   */
  public function getNamespaceUuid(): string {
    /* The default namespace is the UUID generated with
     * Uuid::v5(Uuid::fromString(Uuid::NAMESPACE_DNS), 'reliefweb.int')->toRfc4122(); */
    return '8e27a998-c362-5d1f-b152-d474e1d36af2';
  }

}
