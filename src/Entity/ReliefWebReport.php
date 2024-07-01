<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\ocha_reliefweb\Helpers\FileHelper;

/**
 * ReliefWeb Resource bundle class for the report resource type.
 */
class ReliefWebReport extends ReliefWebResource {

  /**
   * {@inheritdoc}
   */
  public function label() {
    $content = $this->getContent();
    return $content['title'] ?? $this->t('Missing title');
  }

  /**
   * {@inheritdoc}
   */
  public function getApiResource(): string {
    return 'reports';
  }

  /**
   * {@inheritdoc}
   */
  public function getRiver(): string {
    return 'updates';
  }

  /**
   * {@inheritdoc}
   */
  public function submittedContentToApiData(array $content): array {
    $data = [
      'url' => $this->toUrl(),
      'uuid' => $content['uuid'],
      'title' => $content['title'],
      // @todo check if we should pass that to check_markup to ensure all the
      // filters are applied.
      'body-html' => $content['body'],
      'date' => [
        'original' => $content['published'] ?? NULL,
        'created' => gmdate('c', $this->getCreatedTime()),
        'changed' => gmdate('c', $this->getChangedTime()),
      ],
    ];

    // Add the term fields.
    $data += $this->retrieveTermsFromApi([
      'primary_country' => [
        'resource' => 'countries',
        'ids' => array_slice($content['country'] ?? [], 0, 1),
        'fields' => ['name', 'iso3', 'shortname'],
      ],
      'country' => [
        'resource' => 'countries',
        'ids' => array_slice($content['country'] ?? [], 1),
        'fields' => ['name', 'iso3', 'shortname'],
      ],
      'source' => [
        'resource' => 'sources',
        'ids' => $content['source'] ?? [],
        'fields' => ['name', 'shortname'],
      ],
      'format' => [
        'resource' => 'references/content-formats',
        'ids' => $content['format'] ?? [],
        'fields' => ['name'],
      ],
      'language' => [
        'resource' => 'references/languages',
        'ids' => $content['language'] ?? [],
        'fields' => ['name', 'code'],
      ],
      'disaster' => [
        'resource' => 'disasters',
        'ids' => $content['disaster'] ?? [],
        'fields' => ['name'],
      ],
      'disater_type' => [
        'resource' => 'references/disaster-types',
        'ids' => $content['disater_type'] ?? [],
        'fields' => ['name'],
      ],
      'theme' => [
        'resource' => 'references/themes',
        'ids' => $content['theme'] ?? [],
        'fields' => ['name'],
      ],
    ]);

    foreach ($content['file'] ?? [] as $item) {
      $file = $this->loadFile($item);
      if (isset($file)) {
        $data['file'][] = [
          'url' => $this->generateFilePreviewUrl($file),
          'filename' => $file->getFileName(),
          'filesize' => $file->getSize(),
          'mimetype' => $file->getMimeType(),
        ];
      }
    }

    if (isset($content['image'])) {
      $file = $this->loadFile($content['image']);
      if (isset($file)) {
        $image_size = @getimagesize($file->getFileUri());
        $image_url = $this->generateFilePreviewUrl($file);
        $data['image'] = [
          'url' => $image_url,
          'url-small' => $image_url,
          'url-medium' => $image_url,
          'url-large' => $image_url,
          'filename' => $file->getFileName(),
          'filesize' => $file->getSize(),
          'mimetype' => $file->getMimeType(),
          'copyright' => $content['image']['copyright'] ?? '',
          'caption' => $content['image']['description'] ?? '',
          'width' => $image_size[0] ?? NULL,
          'height' => $image_size[1] ?? NULL,
        ];
      }
    }

    return $data;
  }

  /**
   * Generate the preview URL for a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   *
   * @return string
   *   The URL.
   */
  protected function generateFilePreviewUrl(FileInterface $file): string {
    return Url::fromRoute('ocha_reliefweb.file.preview', [
      'file' => $file->id(),
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function apiDataToSubmittedContent(array $data): array {
    $content = [
      'url' => $this->getResourceUrl(),
      'uuid' => $this->getResourceUuid(),
      'title' => $data['title'],
      // @todo check if we should pass that to check_markup to ensure all the
      // filters are applied.
      'body' => $data['body-html'],
      'published' => $data['date']['original'] ?? NULL,
    ];

    $mapping = [
      'primary_country' => 'country',
      'country' => 'country',
      'source' => 'source',
      'format' => 'format',
      'language' => 'language',
      'disaster' => 'disaster',
      'disater_type' => 'disater_type',
      'theme' => 'theme',
    ];

    foreach ($mapping as $api_field => $field) {
      foreach ($data[$api_field] ?? [] as $item) {
        if (isset($item['id'])) {
          $content[$field][] = $item['id'];
        }
      }
    }

    foreach ($data['file'] ?? [] as $file) {
      $content['file'][] = array_filter([
        // @todo convert to `reliefweb://` scheme?
        'url' => $file['url'],
        'uuid' => FileHelper::getFileUuidFromUri($file['url']),
        'filename' => $file['filename'],
        'description' => $file['description'] ?? NULL,
        // @todo check if that is exposed in the API.
        'language' => $file['language'] ?? NULL,
      ]);
    }

    if (isset($data['image'])) {
      $content['image'] = array_filter([
        // @todo convert to `reliefweb://` scheme?
        'url' => $data['image']['url'],
        'uuid' => FileHelper::getFileUuidFromUri($data['image']['url']),
        'copyright' => $file['copyright'] ?? NULL,
        'description' => $file['caption'] ?? NULL,
      ]);
    }

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $status = $this->getStatus();

    // Save the files as permanent so that they are not delete automatically by
    // the system. they will be deleted when the document is published. This
    // allows editors to more easily update the submissions without having to
    // re-upload the files.
    // However, if the document is marked as refused then we mark the files
    // as temporary so they can be removed by the system while still allowing
    // some hours for the editors to do something about the error (ex: timeout).
    //
    // @todo review what to do for update of published submissions.
    foreach ($this->getAttachedFiles() as $file) {
      if ($status === 'pending' && !$file->isPermanent()) {
        $file->setPermanent();
        $file->save();
      }
      elseif (!$file->isTemporary()) {
        $file->setTemporary();
        $file->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Delete the attached files.
    foreach ($entities as $entity) {
      foreach ($entity->getAttachedFiles() as $file) {
        $file->delete();
      }
    }
  }

  /**
   * Retrieve the attachment or image files.
   *
   * @return \Drupal\file\FileInterface[]
   *   List of files keyed by UUID.
   */
  protected function getAttachedFiles(): array {
    $content = $this->getSubmittedContent();
    $files = [];

    if (!empty($content['file'])) {
      foreach ($content['file'] as $item) {
        $file = $this->loadFile($item);
        if (isset($file)) {
          $files[$file->uuid()] = $file;
        }
      }
    }

    if (!empty($content['image'])) {
      $file = $this->loadFile($content['image']);
      if (isset($file)) {
        $files[$file->uuid()] = $file;
      }
    }

    return $files;
  }

  /**
   * Load a file from a the file data.
   *
   * @param array $data
   *   File data as stored in the resource's content. It may contain the file
   *   UUID or its URL.
   *
   * @return ?\Drupal\file\FileInterface
   *   The file if it could be loaded, NULL otherwise.
   */
  protected function loadFile(array $data): ?FileInterface {
    $uuid = $data['uuid'] ?? FileHelper::getFileUuidFromUri($data['url'] ?? '');
    if (!empty($uuid)) {
      return $this->getEntityRepository()->loadEntityByUuid('file', $uuid);
    }
    return NULL;
  }

}
