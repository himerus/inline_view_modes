<?php

namespace Drupal\inline_view_modes\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Class EntityReferenceInlineViewMode.
 *
 * @package Drupal\inline_view_modes\Plugin\Field\FieldType
 *
 * @FieldType(
 *   id = "entity_reference_inline_view_mode",
 *   label = @Translation("Entity Reference w/Custom View Mode"),
 *   description = @Translation("An entity field containing an entity reference with a custom view mode selection option."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_autocomplete_view_mode",
 *   default_formatter = "entity_reference_view_mode_view",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 *
 * @see https://www.lullabot.com/articles/extending-a-field-type-in-drupal-8
 */
class EntityReferenceInlineViewMode extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $view_mode_definition = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('View Mode'))
      ->setRequired(TRUE);
    $properties['view_mode'] = $view_mode_definition;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['view_mode'] = [
      'type' => 'varchar',
      'description' => 'A custom view mode assigned to the reference, allowing it to be displayed as desired by content editors.',
      'length' => 255,
      'not null' => FALSE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   *
   * This method is overridden in order to remove all the default
   * implementations provided by the parent::getPreconfiguredOptions method.
   * This prevents duplicate instances of Content, User, Taxonomy Term, etc.
   * in the new field type dropdown in the reference section.
   */
  public static function getPreconfiguredOptions() {
    $options = [];

    return $options;
  }

}
