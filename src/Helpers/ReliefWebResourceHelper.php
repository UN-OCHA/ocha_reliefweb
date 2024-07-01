<?php

namespace Drupal\ocha_reliefweb\Helpers;

/**
 * Helper to manipulate ReliefWeb resources.
 */
class ReliefWebResourceHelper {

  /**
   * Update the host of API URL fields recursively.
   *
   * Note: this mostly for development to convert the URLs from the API used
   * for dev (ex: stage) to URLs starting with `reliefweb.int`.
   *
   * @param array $data
   *   API data.
   * @param ?string $replacement
   *   Replacement host and scheme.
   * @param string $pattern
   *   Pattern to replace.
   * @param string $recursive
   *   TRUE to also check subfields.
   */
  public static function updateApiDataUrls(
    array &$data,
    string $replacement = NULL,
    string $pattern = '#https?://[^/]+/#',
    bool $recursive = TRUE,
  ): void {
    if (empty($replacement)) {
      $replacement = rtrim(\Drupal::request()->getSchemeAndHttpHost(), '/') . '/';
    }
    foreach ($data as $key => $item) {
      if (is_string($item) && strpos($key, 'url') === 0) {
        $data[$key] = preg_replace($pattern, $replacement, $item);
      }
      elseif (is_array($item) && $recursive) {
        static::updateApiUrls($data[$key], $replacement, $pattern, $recursive);
      }
    }
  }

  /**
   * Get the ReliefWeb URL alias from the given UNOCHA URL.
   *
   * @param string $url
   *   Site URL. If empty use the current URL.
   *   Ex: https://my-site/publications/report/france/report-title.
   * @param string $path
   *   White-label path for the ReliefWeb documents on UNOCHA.
   *
   * @return string
   *   A ReliefWeb URL alias based on the given URL.
   *   Ex: https://reliefweb.int/report/france/report-title.
   */
  public static function getReliefWebUrlFromWhiteLabelledUrl($url = '', $path = 'publications') {
    $url = $url ?: \Drupal::request()->getRequestUri();
    $base_url = \Drupal::config('ocha_reliefweb.settings')->get('reliefweb_website') ?? 'https://reliefweb.int';
    return preg_replace('#(https://[^/]+)?/' . $path . '/#', $base_url . '/', $url);
  }

  /**
   * Get a UNOCHA URL from a ReliefWeb URL alias.
   *
   * @param string $alias
   *   ReliefWeb URL alias.
   *   Ex: https://reliefweb.int/report/france/report-title.
   * @param string $path
   *   White-label path for the ReliefWeb documents on UNOCHA.
   *
   * @return string
   *   A site URL based on the given URL alias.
   *   Ex: https://my-site/publications/report/france/report-title.
   */
  public static function getWhiteLabelledUrlFromReliefWebUrl($alias, $path = 'publications') {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    return preg_replace('#^https?://[^/]+/#', $base_url . '/' . $path . '/', $alias);
  }

  /**
   * Parse the tags for a ReliefWeb resource from the API fields data.
   *
   * @param array $fields
   *   Field data from the ReliefWeb API.
   * @param array $list
   *   List of fields to parse keyed by field name and with tag names as values.
   *
   * @return array
   *   List of tags.
   *
   * @todo make the tags link to somewhere like on ReliefWeb?
   */
  public static function parseResourceTags(array $fields, array $list) {
    $tags = [];

    foreach ($list as $field => $tag) {
      $data = [];
      switch ($field) {
        // Countries.
        case 'country':
          foreach ($fields[$field] ?? [] as $item) {
            $data[] = [
              'name' => $item['name'],
              'shortname' => $item['shortname'] ?? $item['name'],
              'code' => $item['iso3'] ?? '',
              'main' => !empty($item['primary']),
            ];
          }
          break;

        // Sources.
        case 'source':
          foreach ($fields[$field] ?? [] as $item) {
            $data[] = [
              'name' => $item['name'],
              'shortname' => $item['shortname'] ?? $item['name'],
            ];
          }
          break;

        // Languages.
        case 'language':
          foreach ($fields[$field] ?? [] as $item) {
            $data[] = [
              'name' => $item['name'],
              'code' => $item['code'],
            ];
          }
          break;

        // Other more simple tags.
        default:
          foreach ($fields[$field] ?? [] as $item) {
            $data[] = [
              'name' => $item['name'],
            ];
          }
      }
      if (!empty($data)) {
        $tags[$tag] = $data;
      }
    }

    return $tags;
  }

  /**
   * Parse the dates for a river article from the API fields data.
   *
   * @param array $fields
   *   Field data from the ReliefWeb API.
   * @param array $list
   *   Fields to parse keyed by field name and with date names as values.
   *
   * @return array
   *   List of dates.
   */
  public static function parseResourceDates(array $fields, array $list) {
    $dates = [];
    foreach ($list as $field => $date) {
      if (isset($fields['date'][$field])) {
        $dates[$date] = DateHelper::getDateObject($fields['date'][$field]);
      }
    }
    return $dates;
  }

  /**
   * Get the language code from the parse API data.
   *
   * @param array $data
   *   Parsed API data.
   *
   * @return string
   *   The language code for the entity.
   */
  public static function getResourceLanguageCode(array $data) {
    if (isset($data['langcode'])) {
      $langcode = $data['langcode'];
    }
    // Extract the main language code from the entity language tag.
    elseif (isset($data['tags']['language'])) {
      // English has priority over the other languages. If not present we
      // just get the first language code in the list.
      foreach ($data['tags']['language'] as $item) {
        if (isset($item['code'])) {
          if ($item['code'] === 'en') {
            $langcode = 'en';
            break;
          }
          elseif (!isset($langcode)) {
            $langcode = $item['code'];
          }
        }
      }
    }
    return $langcode ?? 'en';
  }

}
