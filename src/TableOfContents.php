<?php

/**
 * TableOfContents.php
 *
 * field_table_of_contents
 */

namespace Drupal\field_table_of_contents;

use DOMDocument;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;

class TableOfContents {
  /**
   * The Node entity that generates the page referenced by this table of
   * contents.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $entity;

  /**
   * Field information used to preprocess the node entity.
   *
   * @var array
   */
  private $fieldInfo = [];

  /**
   * Flag determining whether page references are relative or local.
   *
   * @var bool
   */
  private $isRelative;

  /**
   * The table of contents structure.
   *
   * @var array
   */
  private $structure = [];

  /**
   * Creates a new TableOfContents instance.
   *
   * @param \Drupal\node\Entity\Node
   *  The Node entity on which the table of contents is generating.
   * @param bool $isRelative
   *  Determines whether table of contents references are relative to the page
   *  or absolute.
   */
  public function __construct(Node $entity,bool $isRelative = true) {
    $this->entity = $entity;
    $this->isRelative = $isRelative;
  }

  /**
   * Generates a render array that will render this table of contents.
   *
   * @return array
   */
  public function toRenderArray() : array {
    $render = [
      '#markup' => 'TABLE OF CONTENTS (TEST)',
    ];

    return $render;
  }

  /**
   * Preprocesses the render array for the node entity. This method assumes the
   * indicated render array is for the bound node.
   *
   * @param array &$render
   *  The render array to modify.
   */
  public function preprocess(array &$render) : void {
    foreach ($this->fieldInfo as $fieldName => $fields) {
      foreach ($fields as $delta => $info) {
        if (!isset($render['content'][$fieldName][$delta])) {
          continue;
        }

        $item =& $render['content'][$fieldName]['#items'][$delta];

        if ($info instanceof DOMDocument) {
          $markup = Html::serialize($info);
          $render['content'][$fieldName][$delta] = [
            '#markup' => $markup,
          ];
        }

        unset($item);
      }
    }
  }

  /**
   * Adds a heading to the table of contents structure. The heading is inserted
   * at the current end position.
   *
   * @param string $label
   *  The display label for the heading.
   * @param string $id
   *  The ID used to link the heading to an element in the page.
   * @param bool $inject
   *  
   * @param int $level
   *  The nesting level for the heading.
   */
  public function addHeading(string $label,string $id,bool $inject,int $level = 0) : void {
    $bucket =& $this->structure;
    for ($i = 0;$i < $level;++$i) {
      if (empty($bucket)) {
        $bucket[0] = [
          'id' => null,
          'label' => '',
          'inject' => false,
          'level' => $i,
          'children' => [],
        ];
      }

      $bucket =& $bucket[count($bucket)-1]['children'];
    }

    $bucket[] = [
      'id' => $id,
      'label' => $label,
      'level' => $level,
      'children' => [],
    ];
  }

  /**
   * Adds field info for a field that requires its HTML value replaced with
   * updated content via a DOMDocument instance.
   *
   * @param string $fieldName
   *  The name of the field whose content is represented by the DOM.
   * @param int $delta
   *  The field delta.
   * @param DOMDocument $dom
   *  The DOM representing the parsed field HTML content.
   */
  public function setFieldDOM(string $fieldName,int $delta,DOMDocument $dom) : void {
    $this->fieldInfo[$fieldName][$delta] = $dom;
  }
}
