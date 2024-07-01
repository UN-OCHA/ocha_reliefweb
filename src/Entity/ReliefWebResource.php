<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ocha_reliefweb\Helpers\ReliefWebResourceHelper;
use Drupal\ocha_reliefweb\Helpers\UuidHelper;
use Drupal\ocha_reliefweb\ReliefWebApiClientTrait;
use Drupal\ocha_reliefweb\ReliefWebConfigTrait;
use Drupal\ocha_reliefweb\ReliefWebEntityRepositoryTrait;
use Drupal\user\EntityOwnerTrait;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\JsonPointer;
use Opis\JsonSchema\Validator;

/**
 * Defines the base class for a ReliefWeb Resource entity.
 *
 * @ContentEntityType(
 *   id = "reliefweb_resource",
 *   label = @Translation("ReliefWeb Resource"),
 *   label_collection = @Translation("ReliefWeb Resources"),
 *   label_singular = @Translation("ReliefWeb Resource"),
 *   label_plural = @Translation("ReliefWeb Resources"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ReliefWeb Resource",
 *     plural = "@count ReliefWeb Resources"
 *   ),
 *   bundle_label = @Translation("ReliefWeb Resource type"),
 *   handlers = {
 *     "storage" = "Drupal\ocha_reliefweb\ReliefWebResourceStorage",
 *     "access" = "Drupal\ocha_reliefweb\ReliefWebResourceAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ocha_reliefweb\ReliefWebResourceListBuilder",
 *     "form_builder" = "Drupal\ocha_reliefweb\ReliefWebResourceFormBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ocha_reliefweb\Form\ReliefWebResourceForm",
 *       "add" = "Drupal\ocha_reliefweb\Form\ReliefWebResourceForm",
 *       "edit" = "Drupal\ocha_reliefweb\Form\ReliefWebResourceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\ocha_reliefweb\Routing\ReliefWebResourceHtmlRouteProvider"
 *     }
 *   },
 *   base_table = "reliefweb_resource",
 *   bundle_entity_type = "reliefweb_resource_type",
 *   admin_permission = "administer reliefweb resources",
 *   permission_granularity = "bundle",
 *   translatable = FALSE,
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.reliefweb_resource_type.edit_form",
 *   common_reference_target = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "owner" = "owner",
 *   },
 *   links = {
 *     "add-form" = "/reliefweb-resource/add/{reliefweb_resource_type}",
 *     "edit-form" = "/reliefweb-resource/{reliefweb_resource}/edit",
 *     "delete-form" = "/reliefweb-resource/{reliefweb_resource}/delete",
 *     "canonical" = "/reliefweb-resource/{reliefweb_resource}",
 *     "collection" = "/admin/content/reliefweb-resource",
 *   }
 * )
 */
abstract class ReliefWebResource extends ContentEntityBase implements ReliefWebResourceInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use ReliefWebApiClientTrait;
  use ReliefWebConfigTrait;
  use ReliefWebEntityRepositoryTrait;
  use StringTranslationTrait;

  /**
   * The schema validator.
   *
   * @var \Opis\JsonSchema\Validator
   */
  protected Validator $schemaValidator;

  /**
   * The JSON schema for the content of this entity.
   *
   * This is an associative array with the `raw` JSON string schema and the
   * `decoded` version.
   *
   * @var array
   */
  protected array $jsonSchema;

  /**
   * The resource URL generated from the entity's UUID.
   *
   * @var string
   */
  protected string $resourceUrl;

  /**
   * The API data for the entity.
   *
   * @var ?array
   *
   * @see ::retrieveApiData()
   */
  protected ?array $apiData;

  /**
   * Flag to indicate the content should be submitted.
   *
   * @var bool
   */
  protected bool $submitContent = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    // This includes UUID and bundle fields.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the URL field.
    $fields['resource'] = BaseFieldDefinition::create('reliefweb_resource_id')
      ->setLabel(new TranslatableMarkup('Resource ID'))
      ->setDescription(new TranslatableMarkup('The ReliefWeb resource identifier (UUID).'))
      ->setReadOnly(TRUE);

    // This is a strict minimum to indicate the status of a submitted resource.
    $status_options = [
      'queued' => new TranslatableMarkup('Queued'),
      'pending' => new TranslatableMarkup('Pending'),
      'published' => new TranslatableMarkup('Published'),
      'unpublished' => new TranslatableMarkup('Unpublished'),
      'refused' => new TranslatableMarkup('Refused'),
      'error' => new TranslatableMarkup('Error'),
    ];

    // Add the submission status field.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('Submission status.'))
      ->setReadOnly(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', $status_options)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Add the created field.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time when the entity was created.'));

    // Add the changed field.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time when the provider was last edited.'));

    // Add the changed field.
    $fields['embargoed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Embargo date'))
      ->setDescription(new TranslatableMarkup('The date until which the document remains confidential and unpublished.'));

    // Add the label field.
    // @todo is it necessary or can this be retrieved from the stored
    // submitted data or the data from the RW API?
    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Message'))
      ->setDescription(new TranslatableMarkup('Editorial or error message.'))
      ->setReadOnly(TRUE);

    // Add the submitted content field.
    $fields['content'] = BaseFieldDefinition::create('reliefweb_resource_content')
      ->setLabel(new TranslatableMarkup('Content'))
      ->setDescription(new TranslatableMarkup('The time when the provider was last edited.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Add the owner field.
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent(): array {
    if ($this->getStatus() === 'published') {
      $data = $this->retrieveApiData();
      return $this->apiDataToSubmittedContent($data ?? []);
    }
    elseif ($this->hasSubmittedContent()) {
      return $this->getSubmittedContent();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getApiData(): array {
    if ($this->getStatus() === 'published') {
      return $this->retrieveApiData();
    }
    elseif ($this->hasSubmittedContent()) {
      return $this->submittedContentToApiData($this->content->value);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveApiData(bool $refresh = FALSE): array {
    if ($refresh || !isset($this->apiData)) {
      $endpoint = $this->getApiResource() . '/' . $this->getResourceUuid();
      $data = $this->getReliefWebApiClient()->request('GET', $endpoint);
      $this->apiData = $data['data'][0]['fields'] ?? [];
    }
    return $this->apiData;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->status->value;
  }

  /**
   * Check if there is submitted content.
   *
   * @return bool
   *   TRUE if the entity has submitted content.
   */
  public function hasSubmittedContent(): bool {
    return !empty($this->content->value);
  }

  /**
   * Get the submitted content if any.
   *
   * @return array
   *   Submitted content.
   */
  public function getSubmittedContent(): array {
    return $this->content->value ?? [];
  }

  /**
   * Convert submission data to API data.
   *
   * @param array $content
   *   Submission content.
   *
   * @return array
   *   Data as it would be returned by the read API.
   */
  abstract public function submittedContentToApiData(array $content): array;

  /**
   * Convert API data to submission content.
   *
   * @param array $data
   *   Data returned by the read API.
   *
   * @return array
   *   Reconstructed submission content.
   */
  abstract public function apiDataToSubmittedContent(array $data): array;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->created->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): static {
    return $this->set('created', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitContent(): bool {
    return $this->submitContent;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitContent(bool $submit = TRUE): static {
    $this->submitContent = $submit;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsonSchema(bool $decoded = FALSE): string|array|null {
    if (!isset($this->jsonSchema)) {
      $this->jsonSchema = $this->getReliefWebApiClient()->getPostApiJsonSchema($this->Bundle());
    }
    return $this->jsonSchema[$decoded ? 'decoded' : 'raw'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSchema(array $data): array {
    $data = Helper::toJSON($data);
    $result = $this->getSchemaValidator()->validate($data, $this->getJsonSchema());
    if (!$result->isValid()) {
      $formatter = new ErrorFormatter();
      $errors = $formatter->formatKeyed(
        error: $result->error(),
        formatter: [$this, 'schemaErrorFormatter'],
      );
      return $errors;
    }
    return [];
  }

  /**
   * Custom JSON schema error formatter.
   *
   * This mixes the code from ErrorFormatter::formatErrorMessage() and
   * ErrorFormatter::getDefaultArgs().
   *
   * @param \Opis\JsonSchema\Errors\ValidationError $error
   *   Validation error.
   * @param ?string $message
   *   Error message override.
   *
   * @return string
   *   The formatted error message.
   *
   * @see \Opis\JsonSchema\Errors\ErrorFormatter::formatErrorMessage()
   * @see \Opis\JsonSchema\Errors\ErrorFormatter::getDefaultArgs()
   */
  public function schemaErrorFormatter(ValidationError $error, ?string $message = NULL): string {
    $message ??= $error->message();

    $data = $error->data();
    $info = $error->schema()->info();

    $keyword = $error->keyword();
    if (in_array($keyword, ['not', 'allOf', 'anyOf', 'pattern'])) {
      $info_data = $info->data();
      $keyword_data = $info_data?->{$keyword};

      // The ReliefWeb POST API specifications contain descriptions that are
      // more useful indications of what to do than the obscure regex pattern
      // etc. so we use them are error messages.
      if (isset($keyword_data->description)) {
        return $keyword_data->description;
      }
      elseif (isset($info_data->description)) {
        return $info_data->description;
      }
    }

    // Code from ErrorFormatter::getDefaultArgs().
    $path = $info->path();
    $path[] = $error->keyword();

    $args = [
      'data:type' => $data->type(),
      'data:value' => $data->value(),
      'data:path' => JsonPointer::pathToString($data->fullPath()),

      'schema:id' => $info->id(),
      'schema:root' => $info->root(),
      'schema:base' => $info->base(),
      'schema:draft' => $info->draft(),
      'schema:keyword' => $error->keyword(),
      'schema:path' => JsonPointer::pathToString($path),
    ] + $error->args();

    $args += $error->args();

    // Code from ErrorFormatter::formatErrorMessage().
    if (!$args) {
      return $message;
    }

    return preg_replace_callback(
      '~{([^}]+)}~imu',
      static function (array $m) use ($args) {
        if (!isset($args[$m[1]])) {
          return $m[0];
        }

        $value = $args[$m[1]];

        if (is_array($value)) {
          return implode(', ', $value);
        }

        return (string) $value;
      },
      $message
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaValidator(): Validator {
    if (!isset($this->schemaValidator)) {
      $this->schemaValidator = new Validator();
      // @todo get that from the config?
      // A large value allows to show all the errors in the form.
      $this->schemaValidator->setMaxErrors(50);
    }
    return $this->schemaValidator;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceUuid(): string {
    // Use the stored UUID if defined otherwise generated one.
    // @todo handle the case with API data.
    if (empty($this->resource->value)) {
      $url = $this->getResourceUrl();
      $namespace = $this->getReliefWebApiClient()->getNamespaceUuid();
      $this->resource->value = UuidHelper::generateUuidV5($namespace, $url);
    }
    return $this->resource->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceUrl(): string {
    // Use the stored URL if defined otherwise generated one.
    if (empty($this->resourceUrl)) {
      // Generate a unique URL for the submission. It doesn't need to be an
      // existing URL but it must be unique. We use a UUID V4 (random)for that.
      //
      // @todo retrieve that from the bundle entity so that we can have a
      // different base URL per bundle.
      $base_url = $this->getConfig()?->get('reliefweb_api_submission_base_url');
      if (!isset($base_url)) {
        throw new \Exception('Missing submission base URL');
      }
      $this->resourceUrl = rtrim($base_url, '/') . '/' . $this->uuid();
    }
    return $this->resourceUrl;
  }

  /**
   * Retrieve term data from the RW API.
   *
   * @param array $fields
   *   Associative array of API fields where the values are associative arrays
   *   containing the API resource, the term IDs and the fields to retrieve.
   *
   * @return array
   *   The API fields with the requested data.
   */
  protected function retrieveTermsFromApi(array $fields): array {
    $queries = [];
    foreach ($fields as $field => $data) {
      if (!empty($data['ids'])) {
        $queries[$field] = [
          'method' => 'GET',
          'resource' => $data['resource'],
          'payload' => [
            'profile' => 'minimal',
            'fields' => [
              'include' => array_unique(array_merge([
                'id',
                'name',
              ], $data['fields'] ?? [])),
            ],
            'slim' => 1,
            'filter' => [
              'field' => 'id',
              'value' => $data['ids'],
            ],
            'limit' => count($data['ids']),
          ],
        ];
      }
    }

    $results = $this->getReliefWebApiClient()->requestMultiple($queries);
    foreach ($results as $field => $result) {
      $items = [];
      foreach ($result['data'] ?? [] as $item) {
        if (isset($item['fields'])) {
          $items[] = $item['fields'];
        }
      }
      $results[$field] = $items;
    }

    return array_filter($results);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Ensure the resource UUID is set.
    $this->getResourceUuid();

    // Send the content to the ReliefWeb POST API.
    if ($this->getSubmitContent()) {
      $this->submitContent();
    }
  }

  /**
   * Submit the content to the ReliefWeb POST API.
   */
  protected function submitContent(): void {
    if ($this->hasSubmittedContent()) {
      $config = $this->getConfig();
      $payload = $this->getSubmittedContent();

      $headers = [
        // @todo retrieve that from the bundle entity settings to allow
        // having different provider/key pairs for different resources.
        'X-RW-POST-API-PROVIDER' => $config->get('reliefweb_api_post_api_provider_id'),
        'X-RW-POST-API-KEY' => $config->get('reliefweb_api_post_api_api_key'),
      ];

      $resource = $this->getApiResource() . '/' . $this->getResourceUuid();

      // Submit the content.
      // @todo maybe have submitContent return more than the message so for
      // example we can mark a submissions as refused etc.
      try {
        $message = $this->getReliefWebApiClient()->submitContent($resource, $payload, $headers);
        $this->set('status', 'queued');
        $this->set('message', $message);
      }
      catch (\Exception $exception) {
        $this->set('status', 'error');
        $this->set('message', $exception->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($rel === 'canonical' && $this->getStatus() === 'published') {
      $data = $this->retrieveApiData();
      if (isset($data['url_alias'])) {
        // @todo retrieve the base path from the entity type settings.
        $url = ReliefWebResourceHelper::getWhiteLabelledUrlFromReliefWebUrl($data['url_alias']);
        return Url::fromUri($url);
      }
    }
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Ensure we do not store the API data in the cache so that we don't end up
    // we stale data.
    $this->apiData = NULL;

    return parent::__sleep();
  }

}
