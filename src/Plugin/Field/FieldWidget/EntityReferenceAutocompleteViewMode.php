<?php

namespace Drupal\inline_view_modes\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityReferenceAutocompleteViewMode.
 *
 * @package Drupal\inline_view_modes\Plugin\Field\FieldWidget
 *
 * @FieldWidget(
 *   id = "entity_reference_autocomplete_view_mode",
 *   label = @Translation("Autocomplete w/View Mode"),
 *   description = @Translation("An autocomplete text field with an associated view mode."),
 *   field_types = {
 *     "entity_reference_inline_view_mode"
 *   }
 * )
 *
 * @see https://www.lullabot.com/articles/extending-a-field-type-in-drupal-8
 */
class EntityReferenceAutocompleteViewMode extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $referenced_entity_id = isset($items[$delta]) ? $items[$delta]->target_id : FALSE;
    $options = $this->getFormOptions($referenced_entity_id);

    $parents = implode('-', $element['#field_parents']);
    $class = strlen($parents) > 0 ? $parents . '-' . $delta : $delta;

    $widget['view_mode'] = [
      '#title' => $this->t('View Mode'),
      '#type' => 'select',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->view_mode : 'default',
      '#options' => $options,
      '#min' => 1,
      '#weight' => 10,
      '#prefix' => '<div id="view-mode-selector--delta-' . $class . '">',
      '#suffix' => '</div>',
      '#element_validate' => [
        [get_class($this), 'viewModeValidate'],
      ],
    ];

    // Alter the target_id field to add the appropriate AJAX handlers.
    if (isset($widget['target_id'])) {
      $widget['target_id']['#ajax'] = [
        'event' => 'autocompleteclose',
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

    $parents = implode('-', $triggering_element['#field_parents']);
    $class = strlen($parents) > 0 ? $parents . '-' . $triggering_element['#delta'] : $triggering_element['#delta'];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#view-mode-selector--delta-' . $class,
      '<div id="view-mode-selector--delta-' . $class . '">' . \Drupal::service('renderer')->render($form_field['view_mode']) . '</div>')
    );

    return $response;
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
  public static function viewModeValidate(array $element, FormStateInterface &$form_state) {
    // $values = $form_state->getValues();
    // $triggering_element = $form_state->getTriggeringElement();
    // Add in custom submit handler for Inline View Modes.
    $submit_handlers = $form_state->getSubmitHandlers();
    // Why is $submit_handlers[0] where the previous ones live??
    // Perhaps it seems the submithandlers is set to zero when we want
    // it without any other handlers.
    if (!in_array('viewModeSubmit', $submit_handlers)) {
      $submit_handlers[] = [get_class(self), 'viewModeSubmit'];
      $form_state->setSubmitHandlers($submit_handlers);
    }
  }

  /**
   * Submit handler for inline_view_modes_field_widget_form_alter.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function viewModeSubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $submitted = $form_state->isSubmitted();
    // Get the item that triggered the submit, and ensure it's not 'add_more'.
    $submit_item = array_pop($triggering_element['#parents']);

    if ($submitted && $submit_item != 'add_more') {
      // $values = $form_state->getValues();
    }
  }

}
