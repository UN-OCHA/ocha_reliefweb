<?php

namespace Drupal\ocha_reliefweb;

use Drupal\ocha_reliefweb\Helpers\HtmlSanitizer;
use Drupal\ocha_reliefweb\Helpers\LocalizationHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Custom twig functions.
 */
class TwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('ocha_reliefweb_taglist', [$this, 'getTagList']),
      new TwigFilter('ocha_reliefweb_sanitize_html', [$this, 'sanitizeHtml'], [
        'is_safe' => ['html'],
      ]),
    ];
  }

  /**
   * Get a sorted list of tags.
   *
   * @param array $list
   *   List of tags.
   * @param int $count
   *   Number of items to return, NULL to return all the items.
   * @param string $sort
   *   Porperty to use for sorting.
   *
   * @return array
   *   Sorted and sliced list of tags.
   */
  public static function getTagList(array $list, $count = NULL, $sort = 'name') {
    if (empty($list) || !is_array($list)) {
      return [];
    }
    // Sort the tags if requested.
    if (!empty($sort)) {
      foreach ($list as $key => $item) {
        $sort_value = $item[$sort] ?? $item['name'] ?? $key;
        $list[$key] = [
          // Prefix with a space for the main item (ex: primary country),
          // to ensure it's the first.
          'sort' => (!empty($item['main']) ? ' ' : '') . $sort_value,
          'item' => $item,
        ];
      }
      LocalizationHelper::collatedSort($list, 'sort');
      foreach ($list as $key => $item) {
        $list[$key] = $item['item'];
      }
    }
    // Get the number of items before slicing, this is used to mark the real
    // last item as being last. This way we can also simply check if 'last'
    // is set in the resulting tag list to know if there are more items.
    $last = count($list) - 1;
    // Get a subet of the data if requested.
    if (isset($count)) {
      $list = array_slice($list, 0, $count);
    }
    // Prepare the list of tags, marking the last item.
    $tags = [];
    $index = 0;
    foreach ($list as &$item) {
      $key = $index === $last ? 'last' : $index++;
      $tags[$key] = &$item;
    }
    return $tags;
  }

  /**
   * Sanitize an HTML string, removing unallowed tags and attributes.
   *
   * This also attempts to fix the heading hierarchy, at least preventing
   * the use of h1 and h2 in the sanitized content.
   *
   * @param string $html
   *   HTML string to sanitize.
   * @param bool $iframe
   *   Whether to allow iframes or not.
   * @param int $heading_offset
   *   Offset for the conversion of the headings to perserve the hierarchy.
   * @param array $allowed_attributes
   *   List of attributes that should be preserved (ex: data-disaster-map).
   *
   * @return string
   *   Sanitized HTML string.
   */
  public static function sanitizeHtml($html, $iframe = FALSE, $heading_offset = 2, array $allowed_attributes = []) {
    $sanitizer = new HtmlSanitizer($iframe, $heading_offset, $allowed_attributes);
    return $sanitizer->sanitizeHtml((string) $html);
  }

}
