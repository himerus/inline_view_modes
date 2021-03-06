<?php

namespace Drupal\inline_view_modes\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_view_modes\Helpers\InlineViewModeHelpers;

/**
 * Class EntityReferenceInlineViewModeAutocompleteWidget.
 *
 * @package Drupal\inline_view_modes\Plugin\Field\FieldWidget
 *
 * @FieldWidget(
 *   id = "entity_reference_inline_view_mode_autocomplete_widget",
 *   label = @Translation("Autocomplete w/View Mode"),
 *   description = @Translation("An autocomplete text field with an associated view mode."),
 *   field_types = {
 *     "entity_reference_inline_view_mode"
 *   }
 * )
 *
 * @see https://www.lullabot.com/articles/extending-a-field-type-in-drupal-8
 */
class EntityReferenceInlineViewModeAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   *
   * @todo: Not working with Unlimited value fields.
   * This is working WELL with reference fields with a single value.
   * This also includes a paragraph that has unlimited instances, embedding a
   * reference that is single value. In the test instance (NodeMaker Paragraphs)
   * The Entity Content paragraph allows a single value to be added, yet you can
   * add the paragraph an unlimited amount of times, so the render tree is
   * somehow unique enough to not cause issue.
   *
   * If there is an unlimited value reference field, then it works on the first
   * call to fill out the View Mode form, but if you "Add Another", you get an
   * illegal choice detected error on the original field's View Mode field, and
   * the field is reset, with only 'default' as a choice.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);
    // Check to see if the current user has permissions to alter the View Modes.
    $permission = \Drupal::currentUser()->hasPermission('use inline view modes');

    $referenced_entity_id = isset($items[$delta]) ? $items[$delta]->target_id : FALSE;
    $options = $this->getFormOptions($referenced_entity_id);

    $parents = implode('-', $element['#field_parents']);
    $class = strlen($parents) > 0 ? $parents . '-' . $delta : $delta;

    $default_view_mode = isset($items[$delta]) ? $items[$delta]->view_mode : 'default';
    $target_entity_type = $widget['target_id']['#target_type'];

    if ($target_entity_type && $referenced_entity_id) {
      $entity_data = InlineViewModeHelpers::returnEntityLabels($target_entity_type, $referenced_entity_id);
    }

    $description = isset($entity_data) ? t('Select the appropriate View Mode for the referenced <em><strong>@label</strong></em>, <em>@title</em>.', ['@label' => $entity_data['label'], '@title' => $entity_data['title']]) : t('Enter a reference first...');

    $widget['view_mode'] = [
      '#title' => $this->t('View Mode'),
      '#type' => 'select',
      '#default_value' => $default_view_mode,
      '#options' => $options,
      '#min' => 1 ,
      '#weight' => 10,
      '#access' => $permission ? TRUE : FALSE,
      '#description' => $description,
      '#prefix' => '<div id="view-mode-selector--delta-' . $class . '">',
      '#suffix' => '</div>',
    ];

    // Alter the target_id field to add the appropriate AJAX handlers.
    if (isset($widget['target_id'])) {
      $widget['target_id']['#ajax'] = [
        'event' => 'autocompleteclose',
        'wrapper' => 'view-mode-selector--delta-' . $class,
        'callback' => [get_class($this), 'autocompleteCallback'],
      ];
    }

    return $widget;
  }

  /**
   * Function to return valid options for <select> for Inline View Modes field.
   *
   * Given an $entity_id value, determine the allowed / used view modes by the
   * particular bundle.
   *
   * To send an ENTITY_ID to this function, it should either be the entity_id
   * or FALSE:
   *
   * @code
   * $referenced_entity_id = isset(ENTITY_ID) ? ENTITY_ID : FALSE;
   * @endcode
   *
   * @param int $entity_id
   *   The Entity ID to gather available options for.
   *
   * @return array
   *   Array of View Modes ready for use in select or checkbox form element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getFormOptions($entity_id) {
    if ($entity_id) {
      // Here we have an existing item. Let's get the right view modes.
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($entity_id);
      // @todo: Need to do if (is Class 'node'/'paragraph', etc.) to handle the logic.
      $entity_bundle = $entity->getType();
      $entity_type_id = $entity->getEntityTypeId();
      $entity_view_modes_by_bundle = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle($entity_type_id, $entity_bundle);
      return $entity_view_modes_by_bundle;
    }
    // Provide 'default' view mode as default, then additional appropriate
    // view modes as needed to the array.
    return [
      'default' => 'Default',
    ];
  }

  /**
   * Callback function for inline_view_modes_field_widget_form_alter.
   *
   * Provides callback functionality to grab the appropriate view modes after
   * and entity has been referenced in an autocomplete field.
   *
   * @param array $form
   *   Form object array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of current form.
   *
   * @return mixed
   *   Returns appropriate portion of $form to rebuild via AJAX.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @todo: Figure out why the AJAX is just wonky.
   * This could solve both the issue of $this not being in context to grab the
   * field settings, but could also solve the issue of it not working via
   * anything other than our non-unlimited value setup.
   */
  public function autocompleteCallback(array $form, FormStateInterface $form_state) {
    // @todo: Need some error checking/defensive coding in this callback.
    // Figure out which element triggered the callback.
    $triggering_element = $form_state->getTriggeringElement();
    // Figure out which part of the form to return.
    $form_array_parents = $triggering_element['#array_parents'];
    // Remove the last item 'target_id' from the array_parents to get the
    // appropriate position where the whole widget and view mode field live.
    array_pop($form_array_parents);

    $form_field = NestedArray::getValue($form, $form_array_parents);

    // Figure out the right value to look for in the array.
    $values = $form_state->getValues();
    $form_value_parents = $triggering_element['#parents'];
    $target_value = NestedArray::getValue($values, $form_value_parents);

    $referenced_entity_id = $target_value;
    // Get the allowed/enabled View Modes for the specific entity.
    $allowed_view_modes = self::getFormOptions($referenced_entity_id);
    // Alter the options in the View Mode selector.
    $form_field['view_mode']['#options'] = $allowed_view_modes;

    $default_value = 'default';
    $form_field['view_mode']['#default_value'] = $default_value;

    return $form_field['view_mode'];
  }

  /**
   * Implements #element_validate for inline_view_modes_field_widget_form_alter.
   *
   * Validation here should simply handle doing a final check on the view mode
   * and target_id combination to ensure that they are still a match. This event
   * shouldn't ever fail as the AJAX callback should handle all this and only
   * allow the proper view modes to be selected.
   *
   * Also, here we assign an additional submit handler for the entire form.
   * While the validation can be assigned in widget_form_alter, there's no
   * option to also assign a submit handler for the element:
   * @code
   * '#element_validate' => [
   *   'inline_view_modes_field_widget_form_validate',
   * ],
   * @endcode
   * Also note that $form_state->setSubmitHandlers($submit_handlers); and
   * $form_state->setValidateHandlers($submit_handlers); do nothing when fired
   * inside of hook_field_widget_form_alter, so this method is the only I've
   * found that will actually attach the submit handler without using a standard
   * hook_form_alter, and then having to cycle over every field of every form to
   * determine if it is the appropriate type with the appropriate settings to
   * apply functionality to.
   *
   * @param array $element
   *   Element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function viewModeValidate(array $element, FormStateInterface &$form_state) {

  }

  /**
   * Submit handler for inline_view_modes_field_widget_form_alter.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function viewModeSubmit(array $form, FormStateInterface $form_state) {

  }

}
