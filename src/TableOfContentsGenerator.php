<?php

/**
 * TableOfContentsGenerator.php
 *
 * field_table_of_contents
 */

namespace Drupal\field_table_of_contents;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\node\Entity\Node;
use Drupal\paragraph\Entity\Paragraph;

/**
 * Service that generates a table of contents structure.
 *
 * This generator works by iterating across the fields of a content
 * entity. (Currently, we only support node.) As the generator iterates, it
 * identifies headings to add to the table of contents structure. (This can
 * include nested headings as well.)
 *
 * Headings are identified in several different ways:
 *  1) By interpreting a field value as HTML and parsing for heading tags.
 *  2) By interpreting a field value as a heading.
 *
 * 
 */
class TableOfContentsGenerator {
  /**
   * In-memory cache.
   *
   * @var array
   */
  private $cache = [];

  /**
   * 
   */
  public function __construct() {

  }

  /**
   * Generates a table of contents structure for the indicated node entity.
   *
   * @param \Drupal\node\Entity\Node $node
   *  The node entity to search.
   * @param array $settings

   *  Settings to apply that affect how the table of contents is generated.
   *  - field_types: List of field type machine names that are to be searched for
   *      headings.
   *  - scan_paragraphs: If true, then paragraph fields are also searched.
   *
   * @return \Drupal\field_table_of_contents\TableOfContents
   */
  public function getTableOfContents(Node $node,array $settings = []) : TableOfContents {
    $settings += [
      'field_types' => ['text_long','text_with_summary'],
      'scan_paragraphs' => true,
    ];

    $fieldTypes = $settings['field_types'];
    $scanParagraphs = $settings['scan_paragraphs'];

    $result = new TableOfContents;

    foreach ($node->getFields() as $fieldName => $itemList) {
      foreach ($itemList as $delta => $fieldItem) {
        $fieldDef = $fieldItem->getFieldDefinition();
        $fieldType = $fieldDef->getType();

        // Never process a table of contents field (even if configured).
        if ($fieldType == 'tccl_table_of_contents') {
          continue;
        }

        if ($fieldItem instanceof EntityReferenceItem && $scanParagraphs) {
          if ($fieldItem->entity instanceof Paragraph) {
            $this->processParagraphFields($result,$fieldItem->entity);
            continue;
          }
        }

        // Only process fields having a configured field type.
        if (!in_array($fieldType,$fieldTypes)) {
          continue;
        }

        $value = $fieldItem->getValue();
        $html = $value['value'] ?? null;

        $this->processHtml($result,$html);
      }
    }

    return $result;
  }

  protected function processHtml(TableOfContents $toc,string $html) : void {
    $dom = Html::load($html);
    $xpath = new \DOMXpath($dom);

    $expr = [];
    for ($n = 2;$n <= 4;++$n) {
      $sel = "h$n";
      $expr[] = "//$sel";
    }

    $expr = implode(' | ',$expr);
    $nodes = $xpath->query($expr);

    foreach ($nodes as $node) {

    }
  }

  protected function processParagraphFields(TableOfContents $toc,Paragraph $paragraph) : void {

  }
}
