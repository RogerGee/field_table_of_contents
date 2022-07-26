<?php

/**
 * TableOfContentsGenerator.php
 *
 * field_table_of_contents
 */

namespace Drupal\field_table_of_contents;

use DOMDocument;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Render\Renderer;
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
 *  2) By interpreting a field value as a heading directly.
 */
class TableOfContentsGenerator {
  /**
   * The core rendering service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  private $renderer;

  /**
   * In-memory cache.
   *
   * @var array
   */
  private $cache = [];

  /**
   * Creates a new TableOfContentsGenerator instance.
   *
   * @param \Drupal\Core\Render\Renderer
   */
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Looks up an existing, cached table of contents for the indicated node entity.
   *
   * @param \Drupal\node\Entity\Node $node
   *  The node entity to search.
   *
   * @return \Drupal\field_table_of_contents\TableOfContents
   *  Returns the table of contents if one exists or null if not found.
   */
  public function lookup(Node $node) : ?TableOfContents {
    $nid = $node->id();
    return $this->cache[$nid] ?? null;
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
   * @param bool $cache
   *  Determines if a cached table of contents may be looked up instead of 
   *
   * @return \Drupal\field_table_of_contents\TableOfContents
   */
  public function generate(Node $node,array $settings = [],bool $cache = true) : TableOfContents {
    $nid = $node->id();
    if ($cache && isset($this->cache[$nid])) {
      return $this->cache[$nid];
    }

    $settings += [
      'field_types' => ['text_long','text_with_summary'],
      'scan_paragraphs' => true,
      'is_relative' => true,
    ];

    $fieldTypes = $settings['field_types'];
    $scanParagraphs = $settings['scan_paragraphs'];
    $isRelative = $settings['is_relative'];

    $result = new TableOfContents($node,$isRelative);

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

        // Evaluate the field as HTML by rendering it. NOTE: for now, we always
        // use the 'full' view mode.
        $render = $fieldItem->view('full');
        $html = $this->renderer->renderRoot($render);

        if (!empty($html)) {
          $dom = $this->processHtml($result,$html);

          // If HTML was processed, set field DOM so that field will be
          // preprocessed.
          if ($dom) {
            $result->setFieldDOM($fieldName,$delta,$dom);
          }
        }
      }
    }

    $this->cache[$nid] = $result;

    return $result;
  }

  protected function processHtml(TableOfContents $toc,string $html) : ?DOMDocument {
    $prefix = sha1($html);

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
      $label = $node->nodeValue;

      if (isset($node->attributes['id']) && !empty($node->attributes['id'])) {
        $id = $node->attributes['id'];
      }
      else {
        $id = static::generateId($prefix);

        if ($node->parentNode) {
          // Inject an anchor element for referencing this heading via the ID.
          $anchor = $dom->createElement('a');
          $anchor->setAttribute('id',$id);
          $node->parentNode->insertBefore($anchor,$node);
        }
      }

      if (preg_match('/h([1-9])/',$node->nodeName,$matches)) {
        $level = (int)$matches[1] - 2;
      }
      else {
        $level = 0;
      }

      $toc->addHeading($label,$id,$level);
    }

    if (count($nodes) > 0) {
      return $dom;
    }

    return null;
  }

  protected function processParagraphFields(TableOfContents $toc,Paragraph $paragraph) : void {

  }

  protected static function generateId(string $prefix) : string {
    $s = '';
    for ($i = 0;$i < 16;++$i) {
      if ($i % 2 == 0) {
        $n = rand(65,90);
      }
      else {
        $n = rand(97,122);
      }
      $s .= chr($n);
    }
    return "{$prefix}_{$s}";
  }
}
