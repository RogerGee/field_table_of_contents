<?php

/**
 * TableOfContents.php
 *
 * field_table_of_contents
 */

namespace Drupal\field_table_of_contents;

use DOMDocument;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\node\Entity\Node;

class TableOfContents {
  /**
   * The Node entity that generates the page referenced by this table of
   * contents.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $node;

  /**
   * Field information used to preprocess the node entity.
   *
   * @var array
   */
  private $fieldInfo = [
    'node' => [],
    'paragraph' => [],
  ];

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
   *  The Node entity from which the table of contents entries are generated.
   * @param bool $isRelative
   *  Determines whether table of contents references are relative to the page
   *  or absolute.
   */
  public function __construct(Node $node,bool $isRelative = true) {
    $this->node = $node;
    $this->isRelative = $isRelative;
  }

  /**
   * Generates a render array that will render this table of contents.
   *
   * @return array
   */
  public function toRenderArray() : array {
    $render = [
      '#theme' => 'field_table_of_contents_field',
      '#contents' => $this->structure,
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
  public function preprocessNode(array &$render) : void {
    assert(isset($render['node']) && $render['node'] === $this->node);

    // Extract field information for the node that will are modifying.
    $id = $this->node->id();
    $type = $this->node->bundle();
    $bucket = $this->fieldInfo[$type][$id];

    foreach ($bucket as $fieldName => $fields) {
      foreach ($fields as $delta => $info) {
        if (!isset($render['content'][$fieldName][$delta])) {
          continue;
        }

        $item =& $render['content'][$fieldName]['#items'][$delta];

        // Replace the render array for the field item. This is safe to do since
        // we either 1) reuse the replaced render array in a nested context or
        // 2) replace the array with markup that was already generated from a
        // similar array.

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
   * @param int $level
   *  The nesting level for the heading.
   */
  public function addHeading(string $label,string $id,int $level = 0) : void {
    $bucket =& $this->structure;
    for ($i = 0;$i < $level;++$i) {
      if (empty($bucket)) {
        $bucket[0] = [
          'id' => null,
          'anchor' => '',
          'label' => '',
          'level' => $i,
          'children' => [],
        ];
      }

      $bucket =& $bucket[count($bucket)-1]['children'];
    }

    if ($this->isRelative) {
      $anchorUrl = Url::fromRoute('<none>',[],['fragment' => $id]);
    }
    else {
      $anchorUrl = $this->node->toUrl();
      $anchorUrl->setOption('fragment',$id);
    }

    $bucket[] = [
      'id' => $id,
      'anchor' => [
        '#type' => 'link',
        '#title' => $label,
        '#url' => $anchorUrl,
      ],
      'label' => $label,
      'level' => $level,
      'children' => [],
    ];
  }

  /**
   * Adds field information that is used when the attached entity is
   * preprocessed.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *  The entity that contains the field.
   * @param string $fieldName
   *  The name of the field whose content is represented by the DOM.
   * @param int $delta
   *  The field delta.
   * @param mixed $info
   *  The field information; this can be a number of different values:
   *   - DOMDocument: The field content will be replaced with the saved
   *     representation of this DOM instance.
   *   - string: A unique identifier for the field that will be used to
   *     generate an anchor injected before the field element.
   */
  public function setFieldInfo(ContentEntityBase $entity,string $fieldName,int $delta,$info) : void {
    $id = $entity->id();
    $type = $entity->bundle();

    $this->fieldInfo[$type][$id][$fieldName][$delta] = $info;
  }
}
