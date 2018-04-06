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
 *   id = "entity_reference_inline_view_mode_formatter",
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
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Used to reset the default and remove the default View Mode selection
   * for the Entity Reference fields.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['inline_view_modes_info'] = [
      '#type' => 'markup',
      '#markup' => t('<p>The settings for Inline View Modes is found on the field instance settings (Manage Fields) rather than the display settings.</p>'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Default View Mode settings available on <strong>Manage Fields</strong>');
    return $summary;
  }

}
