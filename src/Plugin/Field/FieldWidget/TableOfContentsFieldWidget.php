<?php

/**
 * TableOfContentsFieldWidget.php
 *
 * field_table_of_contents
 */

namespace Drupal\field_table_of_contents\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a field widget for the table of contents field.
 *
 * @FieldWidget(
 *  id = "tccl_table_of_contents",
 *  label = "Table of Contents Widget",
 *  description = "Configures the table of contents field",
 *  field_types = {
 *    "tccl_table_of_contents"
 *  }
 * )
 */
class TableOfContentsFieldWidget extends WidgetBase {
  /**
   * Converts an array of strings into a multiline string.
   *
   * @param array $array
   *  The array to convert.
   *
   * @return string
   *  Returns the multiline string.
   */
  public static function array2multiline(array $array) {
    return implode("\r\n",$array);
  }

  /**
   * Converts a multiline string into an array of strings.
   *
   * @param array $element
   * @param mixed $input
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public static function multiline2array(array $element,$input,FormStateInterface $form_state) {
    if ($input === false) {
      return [];
    }

    $lines = preg_split('/(\r\n|\r|\n)+/',$input);
    $lines = array_map('trim',$lines);
    $lines = array_filter($lines);

    return array_values($lines);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings += [
      // List of field types that are searched for headings.
      'field_types' => [
        'text_long',
        'text_with_summary',
      ],

      // A list of "bundle:field_name" strings that are interpreted as a pure
      // heading. The type of these fields need not exist in 'field_types'.
      'heading_fields' => [],

      // The list of node bundle types allowed for entities assigned to the
      // table of contents field.
      'node_bundles' => ['page','article'],

      // If a 'paragraph' entity revision field is found, the table of contents
      // will recurse through any child paragraph fields.
      'scan_paragraphs' => true,
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form,FormStateInterface $form_state) {
    $form = parent::settingsForm($form,$form_state);

    $fieldTypePluginManager = \Drupal::service('plugin.manager.field.field_type');
    $fieldTypeOptions = [];
    foreach ($fieldTypePluginManager->getDefinitions() as $name => $info) {
      $fieldTypeOptions[$name] = $info['label'] ?? $name;
    }

    $form['field_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Supported Field Types'),
      '#default_value' => $this->getSetting('field_types'),
      '#description' => $this->t(
        'One or more field type IDs that are searched for headings from which '
        .'to generate the table of contents.'
      ),
      '#options' => $fieldTypeOptions,
      '#multiple' => true,
      '#sort_options' => true,
      '#attributes' => [
        'style' => 'width: 70ch;height: 20em',
      ],
    ];

    $bundleOptions = [];
    $bundleService = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundleService->getBundleInfo('node');
    foreach ($bundles as $name => $bundle) {
      $bundleOptions[$name] = $bundle['label'] ?? $name;
    }

    $entityFieldManager = \Drupal::service('entity_field.manager');
    $moduleHandler = \Drupal::service('module_handler');
    $headingFieldOptions = [];
    foreach ($bundles as $bundle => $bundleInfo) {
      $innerOptions = [];

      foreach ($entityFieldManager->getFieldDefinitions('node',$bundle)
               as $fieldName => $def)
      {
        if (substr($fieldName,0,strlen('field_')) != 'field_') {
          continue;
        }

        $innerOptions["node:$bundle:$fieldName"] = $def->getLabel();
      }

      if (!empty($innerOptions)) {
        $headingFieldOptions[$bundleInfo['label'] ?? $bundle] = $innerOptions;
      }
    }
    if ($moduleHandler->moduleExists('paragraphs')) {
      $paragraphBundles = $bundleService->getBundleInfo('paragraph');
      foreach ($paragraphBundles as $bundle => $bundleInfo) {
        $defs = $entityFieldManager->getFieldDefinitions('paragraph',$bundle);
        foreach ($defs as $fieldName => $def) {
          if (substr($fieldName,0,strlen('field_')) != 'field_') {
            continue;
          }

          $label = "Paragraphs Module ({$bundleInfo['label']})";
          $headingFieldOptions[$label]["paragraph:$bundle:$fieldName"] = $def->getLabel();
        }
      }
    }

    $form['heading_fields'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading Fields'),
      '#default_value' => $this->getSetting('heading_fields'),
      '#description' => $this->t(
        'Zero or more field names that are to be interpreted as headings. '
        .'Note that the field type is not considered for heading fields.'
      ),
      '#options' => $headingFieldOptions,
      '#multiple' => true,
      '#sort_options' => true,
      '#attributes' => [
        'style' => 'width: 70ch;height: 20em',
      ],
    ];

    $form['node_bundles'] = [
      '#type' => 'select',
      '#title' => $this->t('Node Bundles'),
      '#default_value' => $this->getSetting('node_bundles'),
      '#description' => $this->t(
        'The Node bundle types used to filter the Node entity field. Leave none '
        .'selected to allow all.'
      ),
      '#options' => $bundleOptions,
      '#multiple' => true,
      '#sort_options' => true,
      '#attributes' => [
        'style' => 'width: 70ch;height: 20em',
      ],
    ];

    $form['scan_paragraphs'] = [
      '#type' => 'checkbox',
      '#return_value' => 1,
      '#title' => 'Scan Paragraphs',
      '#default_value' => $this->getSetting('scan_paragraphs'),
      '#description' => $this->t(
        'Toggles scanning of Paragraph field instances when generating the '
        .'table of contents.'
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state)
  {
    $title = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $items[$delta]->title ?? '',
      '#description' => $this->t(
        'Indicate a title to render for the table of contents. Leave empty to omit.'
      ),
    ];

    $bundles = $this->getSetting('node_bundles');
    $selectionSettings = [];
    if (!empty($bundles)) {
      $selectionSettings['target_bundles'] = $bundles;
    }

    $nid = $items[$delta]->nid;
    if (is_int($nid) && $nid > 0) {
      $defaultValue = [
        'target_id' => $nid,
      ];
    }
    else {
      $defaultValue = '';
    }

    $nodeId = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Target Page'),
      '#default_value' => $defaultValue,
      '#target_type' => 'node',
      '#selection_handler' => 'default',
      '#selection_settings' => $selectionSettings,
    ];

    $required = $this->getFieldSetting('required');

    $enabled = [
      '#type' => 'checkbox',
      '#return_value' => 1,
      '#title' => $this->t('Enable the table of contents'),
      '#default_value' => ( $required ? 1 : $items[$delta]->enabled ?? 0 ),
      '#disabled' => $required,
    ];

    $element += [
      '#type' => 'fieldset',
      '#open' => true,
      'enabled' => $enabled,
      'title' => $title,
      'nid' => $nodeId,
    ];

    return $element;
  }
}
