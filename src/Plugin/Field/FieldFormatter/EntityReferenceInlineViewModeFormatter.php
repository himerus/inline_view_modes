<?php

namespace Drupal\inline_view_modes\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityReferenceInlineViewModeFormatter.
 *
 * @package Drupal\inline_view_modes\Plugin\Field\FieldFormatter
 *
 * @FieldFormatter(
 *   id = "entity_reference_view_mode_view",
 *   label = @Translation("Rendered Entity w/Custom View Mode"),
 *   description = @Translation("Display the entity with the selected view mode."),
 *   field_types = {
 *     "entity_reference_inline_view_mode"
 *   }
 * )
 *
 * @see https://www.lullabot.com/articles/extending-a-field-type-in-drupal-8
 */
class EntityReferenceInlineViewModeFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    // This 'should' work for an unlimited value field.
    foreach ($elements as $delta => $entity) {
      // Set the View Mode based on the value from the field.
      if ($values[$delta]['view_mode']) {
        $elements[$delta]['#view_mode'] = $values[$delta]['view_mode'];
      }

      // Reset/update the cache tags.
      if (isset($elements[$delta]['#cache']['tags'])) {
        $elements[$delta]['#cache']['tags'][] = $values[$delta]['view_mode'];
        $elements[$delta]['#cache']['keys'][] = $values[$delta]['view_mode'];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: defaultSetting setup.
   * We need to be able to define settings for EACH of the available target
   * entity types. This should be dependent on each field instance.
   */
  public static function defaultSettings() {
    return [
        'default_view_modes' => []
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    /** @var \Drupal\field\Entity\FieldConfig $def */
    $def = $this->fieldDefinition;
    $targets = $def->getSetting('handler_settings')['target_bundles'];
    $bundle = $def->getSetting('target_type');

    $modes = [];

    $elements['dvm_description'] = [
      '#type' => 'markup',
      '#markup' => t('<p>The <em>Default View Mode</em> is used when an entity reference does not specify an <em>Inline View Mode</em> on the reference. <br />For each allowed target entity type below, you can specify a specific view mode to be used as the default.<br />This will also apply if a users role doesn\'t allow them access to edit the <em>Inline View Modes</em>.</p>'),
    ];

    foreach ($targets as $target_id) {
      $target_label = \Drupal::entityTypeManager()
        ->getStorage('node_type')
        ->load($target_id)
        ->label();
      $entity_type_view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle($bundle, $target_id);
      $modes[$target_id] = $entity_type_view_modes;

      $defaults = $this->getSetting('default_view_modes');
      $elements['default_view_modes'][$target_id] = [
        '#type' => 'select',
        '#options' => $entity_type_view_modes,
        '#title' => t('@label: View Mode', ['@label' => $target_label]),
        '#default_value' => $defaults[$target_id] ? $defaults[$target_id] : 'default',
        '#description' => t('Default View Mode for the <em>@label</em> content type.', ['@label' => $target_label]),
        '#required' => TRUE,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $def = $this->fieldDefinition;
    $targets = $def->getSetting('handler_settings')['target_bundles'];
    $bundle = $def->getSetting('target_type');
    foreach ($targets as $target_id) {
      $target_label = \Drupal::entityTypeManager()
        ->getStorage('node_type')
        ->load($target_id)
        ->label();
      $entity_type_view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle($bundle, $target_id);

      $defaults = $this->getSetting('default_view_modes');
      $default = $defaults[$target_id];

      $summary[] = t('<em><strong>@label</strong></em> default view mode: <strong>@target_label</strong> (@target_id)', [
        '@label' => $target_label,
        '@target_label' => $entity_type_view_modes[$default],
        '@target_id' => $default,
      ]);
    }

    return $summary;
  }
}
