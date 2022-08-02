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
use Drupal\node\Entity\Node;

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
      // The list of node bundle types allowed for entities assigned to the
      // table of contents field.
      'node_bundles' => ['page','article'],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form,FormStateInterface $form_state) {
    $form = parent::settingsForm($form,$form_state);

    $bundleOptions = [];
    $bundleService = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundleService->getBundleInfo('node');
    foreach ($bundles as $name => $bundle) {
      $bundleOptions[$name] = $bundle['label'] ?? $name;
    }

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
    if (is_numeric($nid)) {
      $defaultValue = Node::load($nid);
    }
    else {
      $defaultValue = '';
    }

    $nodeId = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Target Page'),
      '#description' => $this->t(
        'Indicate the page that will be targeted for the table of contents. Leave '
        .'empty to render contents for the current page.'
      ),
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
