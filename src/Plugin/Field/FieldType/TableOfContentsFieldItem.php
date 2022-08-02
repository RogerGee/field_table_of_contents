<?php

/**
 * TableOfContentsFieldItem.php
 *
 * field_table_of_contents
 */

namespace Drupal\field_table_of_contents\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type representing a table of contents.
 *
 * @FieldType(
 *  id = "tccl_table_of_contents",
 *  label = "Table of Contents",
 *  category = "TCCL Custom",
 *  description = "Renders a table of contents relating to a content node",
 *  default_widget = "tccl_table_of_contents",
 *  default_formatter = "tccl_table_of_contents"
 * )
 */
class TableOfContentsFieldItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_def) {
    $schema = [];

    $schema['columns']['enabled'] = [
      'type' => 'int',
      'unsigned' => false,
      'size' => 'tiny',
      'not null' => true,
    ];

    $schema['columns']['nid'] = [
      'type' => 'int',
      'not null' => false,
    ];

    $schema['columns']['title'] = [
      'type' => 'varchar',
      'length' => 256,
      'not null' => false,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_def) {
    $properties = [];

    $properties['enabled'] = DataDefinition::create('integer')
                           ->setLabel('Enabled')
                           ->setDescription('Determines if the table of contents is rendered');

    $properties['nid'] = DataDefinition::create('integer')
                           ->setLabel('Node Entity ID')
                           ->setDescription(
                             'The node entity to search for table of contents headings. '
                             .'Leave NULL to indicate the parent entity.'
                           );

    $properties['title'] = DataDefinition::create('string')
                           ->setLabel('Title')
                           ->setDescription('Table of contents title text');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('enabled')->getValue();
    return $value == 0;
  }
}
