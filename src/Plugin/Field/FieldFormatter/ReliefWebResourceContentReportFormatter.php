<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ocha_reliefweb\Entity\ReliefWebResourceInterface;
use Drupal\ocha_reliefweb\Helpers\FileHelper;
use Drupal\ocha_reliefweb\Helpers\HtmlSanitizer;
use Drupal\ocha_reliefweb\Helpers\HtmlSummarizer;
use Drupal\ocha_reliefweb\Helpers\ReliefWebResourceHelper;
use Drupal\ocha_reliefweb\Helpers\UrlHelper;

/**
 * The 'reliefweb_resource_content_report_formatter' formatter plugin.
 *
 * @FieldFormatter(
 *   id = "reliefweb_resource_content_report_formatter",
 *   label = @Translation("ReliefWeb Resource Content Report Formatter"),
 *   field_types = {
 *     "reliefweb_resource_content"
 *   }
 * )
 */
class ReliefWebResourceContentReportFormatter extends ReliefWebResourceContentDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $data = $this->parseResourceApiData($item->getEntity());
      $elements[$delta] = $this->renderContent($data);
    }

    return $elements;
  }

  /**
   * Get the page content.
   *
   * @param array $data
   *   Data as returned by the ReliefWeb API.
   *
   * @return array
   *   Render array.
   */
  public function renderContent(array $data) {
    if (empty($data)) {
      return [];
    }

    $type = isset($data['format']) ? strtolower($data['format']) : 'report';

    $content = [];
    if (!empty($data['attachments'])) {
      $content['attachments'] = $this->renderAttachmentList($data['attachments'], $type);

      if ($type === 'interactive' && !empty($content['attachments'])) {
        $content['attachments']['#title'] = $this->t('Screenshot(s) of the interactive content as of @date', [
          '@date' => $data['published']->format('j m Y'),
        ]);
        if (!empty($data['origin'])) {
          $content['attachments']['#footer'] = Link::fromTextAndUrl(
            $this->t('View the interactive content page'),
            Url::fromUri($data['origin'], [
              'attributes' => [
                'target' => '_blank',
                'rel' => 'noopener',
              ],
            ])
          )->toRenderable();
        }
      }
    }
    if (!empty($data['image'])) {
      $content['image'] = $this->renderImage($data['image']);
    }
    if (!empty($data['body-html'])) {
      $content['body'] = ['#markup' => $data['body-html']];
    }

    // @todo use a different template just for the resource content or
    // find a way to disable the page title for the reliefweb resource
    // entities.
    // For example, disable the "view" option and create a route and controller?
    return [
      '#theme' => 'ocha_reliefweb_document__' . $data['bundle'],
      '#title' => $data['title'],
      '#date' => $data['published'],
      '#content' => $content,
    ];
  }

  /**
   * Render a list of attachemnts.
   *
   * @param array $attachments
   *   List of attachments.
   * @param string $type
   *   One of `map`, `infographic`, `interactive` or `report`.
   *
   * @return array
   *   Render array.
   */
  protected function renderAttachmentList(array $attachments, $type) {
    if (empty($attachments)) {
      return [];
    }

    if ($type === 'map') {
      $label = $this->t('Download map');
      $size = 'large';
    }
    elseif ($type === 'infographic') {
      $label = $this->t('Download infographic');
      $size = 'large';
    }
    elseif ($type === 'interactive') {
      $size = 'large';
    }
    else {
      $type = 'report';
      $label = $this->t('Download attachment');
      $size = 'small';
    }

    $list = [];
    foreach ($attachments as $index => $attachment) {
      $extension = FileHelper::extractFileExtension($attachment['filename']);

      if ($type === 'interactive') {
        $description = $this->t('Screenshot @index', [
          '@index' => $index + 1,
        ]);
        $list[] = [
          'preview' => $this->renderAttachmentPreview($attachment, $size, $description),
          'description' => $description,
        ];
      }
      else {
        $list[] = [
          'url' => $attachment['url'],
          'name' => $attachment['filename'],
          'preview' => $this->renderAttachmentPreview($attachment, $size),
          'label' => $label,
          'description' => '(' . implode(' | ', array_filter([
            mb_strtoupper($extension),
            format_size($attachment['filesize']),
            $attachment['description'] ?? '',
          ])) . ')',
        ];
      }
    }

    return [
      '#theme' => 'ocha_reliefweb_attachment_list__' . $type,
      '#list' => $list,
      '#type' => $type,
    ];
  }

  /**
   * Render an attachment preview.
   *
   * @param array $attachment
   *   The attachment data.
   * @param string $size
   *   Size of the image to use: small or large.
   * @param string $alt
   *   Alternative text for the preview.
   *
   * @return array
   *   Render array.
   */
  public function renderAttachmentPreview(array $attachment, $size = 'small', $alt = '') {
    if (empty($attachment['preview'])) {
      return [];
    }

    return [
      '#theme' => 'image',
      '#uri' => $attachment['preview']['url-' . $size] . '?' . $attachment['preview']['version'],
      '#alt' => $alt ?: $this->t('Preview of @filename', [
        '@filename' => $attachment['filename'],
      ]),
      '#attributes' => [
        'class' => ['ocha-reliefweb-attachment-preview'],
      ],
    ];
  }

  /**
   * Render a report image.
   *
   * @param array $image
   *   Image data.
   *
   * @return array
   *   Render array.
   */
  protected function renderImage(array $image) {
    return [
      '#theme' => 'ocha_reliefweb_entity_image',
      '#image' => $image,
      '#caption' => TRUE,
      '#loading' => 'eager',
    ];
  }

  /**
   * Parse the ReliefWeb API data for the given resource.
   *
   * @param \Drupal\ocha_reliefweb\Entity\ReliefWebResourceInterface $entity
   *   The ReliefWeb resource.
   *
   * @return array
   *   Data ready to be passed to the template for the resource.
   */
  protected function parseResourceApiData(ReliefWebResourceInterface $entity) {
    $fields = $entity->getApiData();

    // @todo retrieve from the bundle entity settings.
    $white_label = $this->getConfig()->get('white_label') ?: FALSE;

    // Title.
    $title = $fields['title'];

    // Summary.
    $summary = '';
    if (!empty($fields['headline']['summary'])) {
      // The headline summary is plain text.
      $summary = $fields['headline']['summary'];
    }
    elseif (!empty($fields['body-html'])) {
      // Summarize the body. The average headline summary length is 182
      // characters so 200 characters sounds reasonable as there is often
      // date or location information at the beginning of the normal body
      // text, so we add a bit of margin to have more useful information in
      // the generated summary.
      $body = HtmlSanitizer::sanitize($fields['body-html']);
      $summary = HtmlSummarizer::summarize($body, 200);
    }

    // Determine document type.
    $format = '';
    if (!empty($fields['format'])) {
      if (isset($fields['format']['name'])) {
        $format = $fields['format']['name'];
      }
      elseif (isset($fields['format'][0]['name'])) {
        $format = $fields['format'][0]['name'];
      }
    }

    // Set the summary if it's empty but there are attachments.
    if (empty($summary) && !empty($fields['file'])) {
      switch ($format) {
        case 'Map':
          $summary = $this->t('Please refer to the attached Map.');
          break;

        case 'Infographic':
          $summary = $this->t('Please refer to the attached Infographic.');
          break;

        case 'Interactive':
          $summary = $this->t('Please refer to the linked Interactive Content.');
          break;

        default:
          if (count($fields['file']) > 1) {
            $summary = $this->t('Please refer to the attached files.');
            break;
          }
          else {
            $summary = $this->t('Please refer to the attached file.');
            break;
          }
      }
    }

    // Tags (countries, sources etc.).
    $tags = ReliefWebResourceHelper::parseResourceTags($fields, [
      'country' => 'country',
      'source' => 'source',
      'language' => 'language',
      'ocha_product' => 'ocha_product',
    ]);

    // Base article data.
    $data = [
      'id' => $entity->id(),
      'bundle' => $entity->bundle(),
      'title' => $title,
      'summary' => $summary,
      'format' => $format,
      'tags' => $tags,
      'body-html' => $fields['body-html'] ?? '',
      'origin' => $fields['origin'] ?? '',
    ];

    // Url to the article.
    // @todo handle case of RW submissions that are not published and so
    // do not have a proper URL alias. Maybe there is nothing to do if
    // the URL does not contain "reliefweb.int".
    if (!empty($data['url_alias'])) {
      if ($white_label) {
        // @todo Retrieve the $path to pass to this method from the
        // bundle entity settings or this formatter settings.
        $data['url'] = ReliefWebResourceHelper::getWhiteLabelledUrlFromReliefWebUrl($fields['url_alias']);
      }
      else {
        $data['url'] = $fields['url_alias'];
      }
    }
    else {
      $data['url'] = $fields['url'];
    }

    // Dates.
    $data += ReliefWebResourceHelper::parseResourceDates($fields, [
      'created' => 'posted',
      'original' => 'published',
    ]);

    // Attachments.
    if (!empty($fields['file'])) {
      $data['attachments'] = $fields['file'];
      // Change the URLs of the attachment to be an unocha.org URL.
      if ($white_label) {
        ReliefWebResourceHelper::updateApiUrls($data['attachments']);
      }

      // Prepare the previews.
      foreach ($data['attachments'] as $index => $attachment) {
        if (isset($attachment['preview'])) {
          $preview = $attachment['preview'];
          $version = $preview['version'] ?? $attachment['id'] ?? 0;

          // Add a the preview version to the URLs to ensure freshness.
          foreach ($preview as $key => $value) {
            if (strpos($key, 'url') === 0) {
              $preview[$key] = UrlHelper::stripDangerousProtocols($value) . '?' . $version;
            }
          }
          $data['attachments'][$index]['preview'] = $preview;

          // Keep track of the first attachment with a preview to use as a
          // thumbnail of the report.
          if (!isset($data['preview'])) {
            $preview_url = $preview['url-thumb'] ?? $preview['url-small'] ?? '';
            if (isset($preview_url)) {
              $data['preview'] = [
                'url' => $preview['url-thumb'] ?? $preview['url-small'],
                // We don't have any good label/description for the file
                // previews so we use an empty alt to mark them as decorative
                // so that assistive technologies will ignore them.
                'alt' => '',
              ];
            }
          }
        }
      }
    }

    // Image.
    if (!empty($fields['image']['url'])) {
      $image = $fields['image'];
      // Add a URL for the medium styles as they are not in the API.
      // @todo add a `url-extra-large` as well if this style is added to
      // ReliefWeb (UNO-771).
      if (isset($image['url-large'])) {
        $image['url-medium'] = str_replace('/large/', '/medium/', $image['url-large']);
      }
      // Change the URLs of the image to be an unocha.org URL.
      if ($white_label) {
        ReliefWebResourceHelper::updateApiUrls($image);
      }
      // Fix the alternative text and copyright.
      $image['alt'] = $image['alt'] ?? $image['caption'] ?? '';
      $image['copyright'] = trim($image['copyright'] ?? '', " \n\r\t\v\0@");
      $data['image'] = $image;
    }

    // Compute the language code from the resource's data.
    $data['langcode'] = ReliefWebResourceHelper::getResourceLanguageCode($data);

    return $data;
  }

}
