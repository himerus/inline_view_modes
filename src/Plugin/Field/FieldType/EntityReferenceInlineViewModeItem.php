<?php

namespace Drupal\inline_view_modes\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Class EntityReferenceInlineViewModeItem.
 *
 * @package Drupal\inline_view_modes\Plugin\Field\FieldType
 *
 * @FieldType(
 *   id = "entity_reference_inline_view_mode",
 *   label = @Translation("Entity Reference w/Custom View Mode"),
 *   description = @Translation("An entity field containing an entity reference with a custom view mode selection option."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_inline_view_mode_autocomplete_widget",
 *   default_formatter = "entity_reference_inline_view_mode_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 *
 * @see https://www.lullabot.com/articles/extending-a-field-type-in-drupal-8
 */
class EntityReferenceInlineViewModeItem extends EntityReferenceItem {

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

  /**
   * {@inheritdoc}
   *
   * @todo: defaultSetting setup.
   * We need to be able to define settings for EACH of the available target
   * entity types. This should be dependent on each field instance.
   */
  public static function defaultFieldSettings() {
    return [
        'default_view_modes' => []
      ] + parent::defaultFieldSettings();
  }

  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    // @todo: Change this to load all the possible options and hide/show via states.


    $targets = $this->getSetting('handler_settings')['target_bundles'];
    $bundle = $this->getSetting('target_type');

    $types = \Drupal::entityManager()->getBundleInfo($bundle);

    $modes = [];

    $elements['inline_view_modes'] = [
      '#type' => 'details',
      '#weight' => 99,
      '#title' => t('Inline View Modes'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];

    $elements['inline_view_modes']['dvm_description'] = [
      '#type' => 'markup',
      '#group' => 'inline_view_modes',
      '#markup' => t('<p>The <em>Default View Mode</em> is used when an entity reference does not specify an <em>Inline View Mode</em> on the reference. <br />For each allowed target entity type below, you can specify a specific view mode to be used as the default.<br />This will also apply if a users role doesn\'t allow them access to edit the <em>Inline View Modes</em>.</p>'),
    ];

    $elements['default_view_modes'] = [
      '#type' => 'container',
      '#group' => 'inline_view_modes',
    ];

    foreach ($types as $target_id => $target_label) {
      $target_label = $target_label['label'];
      $entity_type_view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle($bundle, $target_id);
      $modes[$target_id] = $entity_type_view_modes;

      $defaults = $this->getSetting('default_view_modes');
      $elements['default_view_modes'][$target_id] = [
        '#type' => 'select',
        '#options' => $entity_type_view_modes,
        '#tree' => TRUE,
        '#title' => t('@label: View Mode', ['@label' => $target_label]),
        '#default_value' => isset($defaults[$target_id]) ? $defaults[$target_id] : 'default',
        '#description' => t('Default View Mode for the <em>@label</em> content type.', ['@label' => $target_label]),
        '#required' => TRUE,
      ];
    }

    return $elements + parent::fieldSettingsForm($form, $form_state);
  }
}
