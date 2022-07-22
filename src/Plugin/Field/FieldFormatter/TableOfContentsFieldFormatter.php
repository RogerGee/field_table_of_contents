<?php

/**
 * TableOfContentsFieldFormatter.php
 *
 * field_table_of_contents
 */

namespace Drupal\field_table_of_contents\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a field formatter for rendering a table of contents.
 *
 * @FieldFormatter(
 *  id = "tccl_table_of_contents",
 *  label = "Table of Contents",
 *  field_types = {
 *    "tccl_table_of_contents"
 *  }
 * )
 */
class TableOfContentsFieldFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form,FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items,$langcode = null) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => 'TABLE OF CONTENTS (TEST)',
      ];
    }

    return $elements;
  }
}
