# Drupal Field Table of Contents

This project provides a field type for rendering a table of contents.

## Installation

Install via Composer if using TCCL's private repositories. Otherwise download a release and install manually in your Drupal project. (This project is currently not hosted on `drupal.org`.)

## Overview

This project provides a custom Drupal module implementing a custom field type. The field type renders a table of contents for a page. The table of contents is automatically generated using content on the target page and automatically hyperlinked to the referenced content in the page. The target page can either be the same page containing the table of contents field or another page.

Since the table of contents is rendered using a field, you can have multiple tables of contents on a page. This is useful for creating a complete table of contents for content spanning multiple pages.

**Explanation of Table Generation**:
The module generates a table of contents using one of two mechanisms. Each mechanism derives heading entries from field content:

1. Parsing HTML from fields having a particular field type (e.g. `text_long`)
	- This method parses HTML for heading elements (e.g. `<h2>`) and constructs the table of contents from the text of the headings.
	- The level of the heading (e.g `<h2>` vs `<h3>`) is respected. Note that, by convention, the module interprets `<h2>` as a top-level heading, since in Drupal, the `<h1>` is typically reserved for the page title.
	- The module preprocesses the field HTML and injects fragment anchors that are targeted by links in the table of contents.
2. Interpreting a configured field as a top-level heading
	- This method interprets an entire field value as a heading directly, using the string representation of the field value as the text of the heading.
	- This method is useful when you have a dedicated field that represents a heading for a section of the page.
	- Such heading fields are always interpreted as a top-level heading. Child headings can still be parsed from subsequent fields using the first method (starting with `<h3>`).

**Paragraphs**:
If the module encounters an entity reference field item and if the linked entity is a _Paragraph_ (i.e. from the `paragraphs` module), then the module will recursively process that entity and derive heading entries from its fields. This behavior is only enabled if the _Scan Paragraphs_ option is configured in the field display settings.

## Configuration

**Form Display Settings**

- _Node Bundles_: The set of Node content types (i.e. bundles) that are allowed when setting a linked Node entity on a table of contents field. This set is used for filtering the entity autocomplete widget.

**Display Settings**

- _Supported Field Types_: The set of field types that determine which fields are processed via the HTML parsing method (#1). Defaults to _Text (formatted, long)_ and _Text (formatted, long, with summary)_
- _Heading Fields_: The set of fields that are processed via the heading field method (#2).
- _Scan Paragraphs_: Enables recursive paragraph entity processing.

## Theming

This module provides a theme template for rendering the table of contents: `field-table-of-contents-field.twig.html`. You can use the default template as a basis for a custom override. Since the table of contents is a tree structure, you will need a macro for recursively generating it.
