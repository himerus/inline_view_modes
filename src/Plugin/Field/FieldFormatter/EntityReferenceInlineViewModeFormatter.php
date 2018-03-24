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
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('Default View mode'),
      '#description' => t('<p>The <em>Default View Mode</em> is used when an entity reference did not specify an <em>Inline View Mode</em>. This should be used carefully and usually set to <em>Default</em> since you cannot be sure that allowed types assgined to an <em>Entity Reference</em> field has the same view modes available.</p>'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $summary[] = t('Default View Mode: @mode', ['@mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode]);

    return $summary;
  }

}
