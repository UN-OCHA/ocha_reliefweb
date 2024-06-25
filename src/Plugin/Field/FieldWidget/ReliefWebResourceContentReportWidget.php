<?php

declare(strict_types=1);

namespace Drupal\ocha_reliefweb\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\file\FileInterface;
use Drupal\ocha_reliefweb\Helpers\FileHelper;

/**
 * The 'reliefweb_resource_content_report_widget' widget plugin.
 *
 * @FieldWidget(
 *   id = "reliefweb_resource_content_report_widget",
 *   label = @Translation("ReliefWeb Resource Content Report Widget"),
 *   field_types = {
 *     "reliefweb_resource_content"
 *   }
 * )
 */
class ReliefWebResourceContentReportWidget extends ReliefWebResourceContentDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $field = $this->fieldDefinition->getName();
    $parents = array_merge($form['#parents'], [$field, $delta]);

    // @todo build the form based on the JSON schema? That would would make
    // it more flexible but it may be difficult to identify which fields need
    // data from the API.
    $entity = $form_state->getFormObject()->getEntity();
    $schema = $entity->getJsonSchema(TRUE);
    $schema_fields = $schema['properties'];
    $required_fields = array_flip($schema['required']);

    $default_path = array_merge($parents, ['value']);
    $defaults = $this->prepareFormDefaults($items, $delta);
    $defaults = $form_state->getValue($default_path, $defaults);
    // Ensure the form state stores all the default values.
    $form_state->setValue($default_path, $defaults);

    $element['#type'] = 'container';
    $element['#tree'] = TRUE;
    $element['#parents'] = $parents;

    $element['value'] = [
      '#type' => 'container',
    ];

    $element['value']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $defaults['title'] ?? NULL,
    ];

    // @todo we need to provide a text format with a default editor.
    $element['value']['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $defaults['body'] ?? NULL,
      '#format' => 'ocha_reliefweb_editor',
      '#allowed_formats' => ['ocha_reliefweb_editor'],
    ];

    // @todo add a config option or another way to specify the default
    // value for this field. (For example OCHA for the unocha.org site).
    $element['value']['source'] = $this->createTermSelector(
      resource: 'sources',
      label: $this->t('Source'),
      description: $this->t('Source(s) of the document.'),
      fields: ['shortname'],
      default_value: $defaults['source'] ?? NULL,
    );

    // @todo add a config option or another way to specify the default
    // value for this field. (For example based on the cluster on RWR).
    $element['value']['primary_country'] = $this->createTermSelector(
      resource: 'countries',
      label: $this->t('Primary country'),
      description: $this->t('Primarily focused country in the document.'),
      fields: ['shortname'],
      default_value: $defaults['primary_country'] ?? NULL,
    ) + ['#required' => TRUE];

    $element['value']['country'] = $this->createTermSelector(
      resource: 'countries',
      label: $this->t('Country'),
      description: $this->t('Other country(ies) focused on in the document.'),
      fields: ['shortname'],
      default_value: $defaults['country'] ?? NULL,
    );

    $element['value']['format'] = $this->createTermSelector(
      resource: 'references/content-formats',
      label: $this->t('Format'),
      description: $this->t('Content format of the document.'),
      default_value: $defaults['format'] ?? NULL,
    );

    $element['value']['language'] = $this->createTermSelector(
      resource: 'references/languages',
      label: $this->t('Language'),
      description: $this->t('Language(s) of the document.'),
      default_value: $defaults['language'] ?? NULL,
    );

    $element['value']['published'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Publication date (UTC)'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_year_range' => '-3:0',
      '#date_timezone' => 'UTC',
      '#default_value' => $defaults['published'] ?? new DrupalDateTime(),
    ];

    $element['value']['disaster'] = $this->createTermSelector(
      resource: 'disasters',
      label: $this->t('Disaster'),
      description: $this->t('Disaster(s) focused on in the document.'),
      fields: ['glide'],
      default_value: $defaults['disaster'] ?? NULL,
    );

    // @todo Do we want to have the same logic as the RW form with the disaster
    // type field populated with the types of the selected disasters?
    $element['value']['disaster_type'] = $this->createTermSelector(
      resource: 'references/disaster-types',
      label: $this->t('Disaster'),
      description: $this->t('Disaster type(s) focused on in the document.'),
      default_value: $defaults['disaster_type'] ?? NULL,
    );

    $element['value']['theme'] = $this->createTermSelector(
      resource: 'references/themes',
      label: $this->t('Theme'),
      description: $this->t('Theme(s) focused on in the document.'),
      default_value: $defaults['theme'] ?? NULL,
    );

    // @todo add a config option to enable/disable this field.
    $element['value']['origin'] = [
      '#type' => 'url',
      '#title' => $this->t('Origin'),
      '#description' => $this->t('Origin URL of the document.'),
      '#default_value' => $defaults['origin'] ?? NULL,
    ];

    // @todo add a config option to enable/disable this field.
    $element['value']['notify'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notify'),
      '#description' => $this->t('List of email addresses to notify when the document is published.'),
      '#default_value' => $defaults['notify'] ?? NULL,
    ];

    $element['value']['embargoed'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Embargo date (UTC)'),
      '#description' => $this->t('Date until which publication is restricted.'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_year_range' => '0:+3',
      '#date_timezone' => 'UTC',
      '#default_value' => $defaults['embargoed'] ?? NULL,
    ];

    foreach (Element::children($element['value']) as $key) {
      if (isset($schema_fields[$key]['maxItems']) && $schema_fields[$key]['maxItems'] > 1) {
        $element['value'][$key]['#multiple'] = TRUE;
      }
      if (isset($required_fields[$key])) {
        $element['value'][$key]['#required'] = TRUE;
      }
    }

    // The country field is not mandatory since we have the primary country.
    $element['value']['country']['#required'] = FALSE;

    // Add the form element to upload attachments.
    $this->addAttachmentsFormElement($element, $form_state);

    // Add the form element to upload an image.
    $this->addImageFormElement($element, $form_state);

    // @todo add the form elements.
    return $element;
  }

  /**
   * Generate the attachment upload form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $defaults
   *   The default values for the attachments form element.
   */
  protected function addAttachmentsFormElement(array &$element, FormStateInterface $form_state): void {
    $entity = $form_state->getFormObject()->getEntity();
    $schema = $entity->getJsonSchema(TRUE);
    if (!isset($schema['properties']['file']['maxItems'])) {
      return;
    }
    $max_files = $schema['properties']['file']['maxItems'];

    $parents = array_merge($element['#parents'], ['value', 'attachments']);
    $wrapper = implode('-', array_merge($parents, ['wrapper']));

    $element['value']['attachments'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Attachments'),
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper . '">',
      '#suffix' => '</div>',
    ];

    // Retrieve the default values for the attachments like the description.
    $defaults = $form_state->getValue(array_merge($parents, ['list'])) ?: [];

    // Initialize the form element with at least one row to upload a file
    // to reduce the number of clicks.
    // @todo we need to initialize that when editing a document.
    $attachments = $form_state->get($parents);

    // Initialize the list of attachments.
    if (!isset($attachments)) {
      if (!empty($defaults)) {
        $attachments = array_filter(array_map(function ($item) {
          return $item['file'] ?? NULL;
        }, $defaults ?? []));
      }
      else {
        $attachments = [];
      }
      $form_state->set($parents, $attachments);
    }

    // Retrieve the default values for the attachments like the description.
    foreach (array_keys($attachments) as $key) {
      $attachments[$key] = $defaults[$key] ?? NULL;
    }

    // We put the attachments in a table that we can re-order.
    $element['value']['attachments']['list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('File'),
        $this->t('Description'),
        $this->t('Language'),
        $this->t('Weight'),
        // @todo add colspan or something to handle the checksum and original
        // uuid columns.
      ],
      '#empty' => $this->t('No files.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'file-weight',
        ],
      ],
      '#access' => !empty($attachments),
    ];

    // Generate the upload location for the attachments.
    // @todo retrieve the base location from the configuration.
    $upload_location = 'public://reliefweb-submissions/attachments/' . $entity->getResourceUuid() . '/';

    $file_storage = $this->getEntityTypeManager()->getStorage('file');

    // Generate the list of attachments.
    foreach ($attachments as $delta => $default) {
      $file = NULL;
      if (isset($default['file'][0])) {
        $file = $file_storage->load($default['file'][0]);
      }

      $element['value']['attachments']['list'][$delta]['#attributes']['class'][] = 'draggable';
      // This is required to order the attachments by weight, for example
      // when re-ordered.
      $element['value']['attachments']['list'][$delta]['#weight'] = $default['weight'] ?? $delta;

      // @todo when editing, show a link to the file with a replace button
      // instead. When clicking this replace button, show a new upload widget.
      $element['value']['attachments']['list'][$delta]['file'] = [
        '#type' => 'managed_file',
        // @todo retrieve the location from the configuration.
        '#upload_location' => $upload_location,
        '#upload_validators' => [
          'FileExtension' => ['extensions' => 'pdf'],
          'FileNameLength' => [],
          // @todo retrieve that from the config?
          'FileSizeLimit' => ['fileLimit' => 20 * 1024 * 1024],
        ],
        '#default_value' => $default['file'] ?? NULL,
        '#accept' => '.pdf',
      ];

      $element['value']['attachments']['list'][$delta]['description'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Description for file on row @number', [
          '@number' => $delta + 1,
        ]),
        '#title_display' => 'invisible',
        '#default_value' => $default['description'] ?? NULL,
      ];

      $element['value']['attachments']['list'][$delta]['language'] = [
        '#type' => 'select',
        '#title' => $this->t('Language for file on row @number', [
          '@number' => $delta + 1,
        ]),
        '#title_display' => 'invisible',
        '#options' => [
          'ar' => $this->t('Arabic'),
          'en' => $this->t('English'),
          'es' => $this->t('Spanish'),
          'fr' => $this->t('French'),
          'ru' => $this->t('Russian'),
        ],
        '#default_value' => $default['language'] ?? NULL,
        '#empty_option' => $this->t('- Select language -'),
      ];

      // Visually hidden field to store the actual weight when re-ordering.
      $element['value']['attachments']['list'][$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for file on row @number', [
          '@number' => $delta + 1,
        ]),
        '#title_display' => 'invisible',
        '#default_value' => $default['weight'] ?? $delta,
        '#attributes' => ['class' => ['file-weight']],
      ];

      // Store the file checksum.
      $checksum = NULL;
      if (isset($file)) {
        if (!empty($default['checksum'])) {
          $checksum = $default['checksum'];
        }
        else {
          $checksum = $this->getFileChecksum($file);
        }
      }
      $element['value']['attachments']['list'][$delta]['checksum'] = [
        '#type' => 'hidden',
        '#value' => $checksum,
      ];

      // Store the original file UUID. We need this to be able to replace the
      // file.
      if (!empty($default['uuid'])) {
        $original_uuid = $default['uuid'];
      }
      else {
        $original_uuid = $file?->uuid();
      }
      $element['value']['attachments']['list'][$delta]['uuid'] = [
        '#type' => 'hidden',
        '#value' => $original_uuid,
      ];
    }

    // Sort the attachments by weight while preserving their delta which is
    // important for Drupal to map those form elements to the actual uploaded
    // files.
    Element::children($element['value']['attachments']['list'], TRUE);

    // Add a button to add more files.
    if (count($attachments) <= $max_files) {
      $element['value']['attachments']['add_file'] = [
        '#type' => 'submit',
        '#name' => 'add_file',
        '#value' => $this->t('Add a file'),
        '#submit' => [[$this, 'addAttachmentSubmit']],
        '#ajax' => [
          'callback' => [$this, 'addAttachmentCallback'],
          'wrapper' => $wrapper,
        ],
        '#limit_validation_errors' => [
          $parents,
        ],
      ];
    }
    else {
      $element['value']['attachments']['limit_reached'] = [
        '#markup' => $this->t('Attachment limit reached.'),
      ];
    }
  }

  /**
   * Generate the checksum for a file.
   *
   * @param \Drupal\file\FileInterface|null $file
   *   The file.
   *
   * @return string|null
   *   The checksum.
   */
  protected function getFileChecksum(?FileInterface $file): ?string {
    if (is_null($file)) {
      return NULL;
    }
    return hash_file('sha256', $file->getFileUri()) ?: NULL;
  }

  /**
   * Generate the image upload form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function addImageFormElement(array &$element, FormStateInterface $form_state): void {
    $entity = $form_state->getFormObject()->getEntity();
    $parents = array_merge($element['#parents'], ['value', 'image']);
    $defaults = $form_state->getValue($parents) ?: [];

    // Generate the upload location for the images.
    // @todo retrieve the base location from the configuration.
    $upload_location = 'public://reliefweb-submissions/images/' . $entity->getResourceUuid() . '/';

    $element['value']['image'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image'),
      '#tree' => TRUE,
    ];
    $element['value']['image']['file'] = [
      '#type' => 'managed_file',
      // @todo retrieve the location from the configuration.
      '#upload_location' => $upload_location,
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'jpg png webp'],
        'FileNameLength' => [],
        // @todo retrieve that from the config?
        'FileSizeLimit' => ['fileLimit' => 5 * 1024 * 1024],
        'FileImageDimensions' => [
          'maxDimensions' => '2048x2048',
          'minDimensions' => '700x200',
        ],
      ],
      '#default_value' => $defaults['file'] ?? NULL,
      '#accept' => '.jpg,.png,.webp',
    ];

    $element['value']['image']['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $defaults['description'] ?? NULL,
      '#maxlength' => 512,
    ];

    $element['value']['image']['copyright'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright'),
      '#default_value' => $defaults['copyright'] ?? NULL,
      '#maxlength' => 512,
    ];
  }

  /**
   * Submit callback to add a new attachment element.
   */
  public function addAttachmentSubmit(array &$form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);

    // An empty element. This will result in the addition of a new file managed
    // form element in the attachments table.
    $attachments = $form_state->get($parents);
    $attachments[] = NULL;
    $form_state->set($parents, $attachments);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback to add an attachment.
   *
   * @param array $form
   *   The entire form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The attachment form element.
   */
  public function addAttachmentCallback(array $form, FormStateInterface $form_state): array {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);
    return NestedArray::getValue($form, $parents) ?? $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataFromForm(array $values, array $form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();

    // @todo handle attachments and image!
    return array_filter([
      'url' => $entity->getResourceUrl(),
      'uuid' => $entity->getResourceUuid(),
      'title' => $values['title'] ?? '',
      'body' => (string) check_markup($values['body']['value'] ?? '', 'ocha_reliefweb_editor'),
      'source' => $this->toIntArray($values['source'] ?? []),
      // @todo order the countries by alpha.
      'country' => array_values(array_unique(array_merge(
        $this->toIntArray($values['primary_country'] ?? []),
        $this->toIntArray($values['country'] ?? []),
      ))),
      'language' => $this->toIntArray($values['language'] ?? []),
      'format' => $this->toIntArray($values['format'] ?? []),
      'published' => $this->toIsoDate($values['published'] ?? []),
      'disaster' => $this->toIntArray($values['disaster'] ?? []),
      'disaster_type' => $this->toIntArray($values['disaster_type'] ?? []),
      'theme' => $this->toIntArray($values['theme'] ?? []),
      'origin' => $values['origin'] ?? '',
      'notify' => $this->toStringArray($values['notify'] ?? ''),
      'embargoed' => $this->toIsoDate($values['embargoed'] ?? NULL),
      'file' => $this->toAttachmentArray($values['attachments']['list'] ?? []),
      'image' => $this->toImageItem($values['image'] ?? []),
    ]);
  }

  /**
   * Convert a list of uploaded files to attachments as expected by the API.
   *
   * @param mixed $data
   *   List of attachments from the form. It can be a string if there was
   *   not data for some Drupaly reason...
   *
   * @return array
   *   List of attachments as expected by the ReliefWeb POST API.
   */
  public function toAttachmentArray(mixed $data): array {
    if (!is_array($data)) {
      return [];
    }
    $storage = $this->getEntityTypeManager()->getStorage('file');

    $attachments = [];
    foreach ($data as $item) {
      if (!isset($item['file'][0])) {
        continue;
      }

      $file = $storage->load($item['file'][0]);
      if (!isset($file)) {
        continue;
      }

      $attachments[] = array_filter([
        'url' => $file->createFileUrl(FALSE),
        'uuid' => !empty($item['uuid']) ? $item['uuid'] : $file->uuid(),
        'filename' => $file->getFilename(),
        'checksum' => !empty($item['checksum']) ? $item['checksum'] : $this->getFileChecksum($file),
        'description' => $item['description'] ?? '',
        'language' => $item['language'] ?? '',
      ]);
    }
    return $attachments;
  }

  /**
   * Convert a list of uploaded images to images as expected by the API.
   *
   * @param array $data
   *   Image form data.
   *
   * @return array
   *   Image data as expected by the ReliefWeb POST API.
   */
  public function toImageItem(array $data): array {
    $storage = $this->getEntityTypeManager()->getStorage('file');

    if (!isset($data['file'][0])) {
      return [];
    }

    $file = $storage->load($data['file'][0]);
    if (!isset($file)) {
      return [];
    }

    return array_filter([
      'url' => $file->createFileUrl(FALSE),
      'uuid' => $data['uuid'] ?? $file->uuid(),
      'checksum' => $data['checksum'] ?? $this->getFileChecksum($file),
      'description' => $data['description'] ?? '',
      'copyright' => $data['copyright'] ?? '',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDefaults(FieldItemListInterface $items, $delta) {
    $defaults = $items->get($delta)?->get('value')?->getValue() ?? [];
    if (!empty($defaults['published'])) {
      $defaults['published'] = new DrupalDateTime($defaults['published']);
    }
    // @todo it's not convenient not have separate fields, consider changing
    // the specifications.
    if (empty($defaults['primary_country']) && !empty($defaults['country'])) {
      $defaults['primary_country'] = [array_shift($defaults['country'])];
    }
    // Load the file info for each attachments.
    if (!empty($defaults['file'])) {
      foreach ($defaults['file'] as $item) {
        $defaults['attachments']['list'][] = $this->setFileDefault($item);
      }
      unset($defaults['file']);
    }
    // Load the file info for the image.
    if (!empty($defaults['image'])) {
      $defaults['image'] = $this->setFileDefault($defaults['image']);
    }

    return $defaults;
  }

  /**
   * Set the file information to the default data for a file/image form element.
   *
   * @param array $data
   *   The default data.
   *
   * @return array
   *   The default data with the file information.
   */
  protected function setFileDefault(array $data): array {
    if (isset($data['uuid'])) {
      $uuid = $data['uuid'];
    }
    elseif (isset($data['url'])) {
      $uuid = FileHelper::getFileUuidFromUri($data['url']);
    }

    if (!empty($uuid)) {
      $files = $this->getEntityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uuid' => $uuid]);
      if (!empty($files)) {
        $data['file'] = [key($files)];
      }
    }
    return $data;
  }

}
