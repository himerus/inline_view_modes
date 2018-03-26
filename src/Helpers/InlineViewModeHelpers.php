<?php

namespace Drupal\inline_view_modes\Helpers;

use Drupal\Core\Entity\Entity;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;

/**
 * ThemeBuilder declares methods used to build a new subtheme.
 */
class InlineViewModeHelpers {

  /**
   * NodemakerHelpers constructor.
   */
  public function __construct() {

  }

  /**
   * Function to return TRUE/FALSE if Inline View Modes are allowed.
   *
   * Custom function to check $field against an array of $allowed_field_types
   * and $allowed_entity_types to determine if the field is capable/allowed
   * to use Inline View Modes.
   *
   * Possible future additions to $allowed_parent_entity_types and
   * $allowed_target_entity_types:
   * - comment -> Comment
   * - block_content -> Custom Block
   * - node -> Content
   * - content_moderation_state -> Content moderation state
   * - taxonomy_term -> Taxonomy term
   * - user -> User
   * - paragraph -> Paragraph
   *
   * The following code demonstrates finding the appropriate
   * parent/target entity.
   * @code
   * $parent_entity = $field->getTargetEntityTypeId();
   * $target_type = $field->getSetting('target_type');
   * @endcode
   *
   * @param \Drupal\field\Entity\FieldConfig $field
   *   Field entity used to determine if Inline View Modes is enabled.
   *
   * @todo: Integrate permissions?
   * @todo: Expand parent & target entity types available.
   * @todo: Refactor target/parent entity type logic to be more appropriate.
   *   This should likely be more like the following:
   *   - IF the PARENT entity allows the entity_reference field, then it
   *     shouldn't matter or require a check and would be fine to PROCEED.
   *   - IF the TARGET entity is a Content Entity that allows/has view modes,
   *     then it should be fine to PROCEED.
   *   - IF the FIELD type is one of the ones we want, PROCEED.
   *
   * @return bool
   *   If Inline View Modes is allowed or not.
   */
  public static function inlineViewModesAllowed(FieldConfig $field) {
    // Entity types capable of adding Inline View Modes functionality to.
    $allowed_parent_entity_types = [
      'node',
      'paragraph',
    ];
    // Array of content entities with view modes.
    $allowed_target_entity_types = self::getEntitiesWithViewModes();
    // Allowed field types capable of adding Inline View Modes functionality to.
    $allowed_field_types = [
      'entity_reference',
      'entity_reference_revisions',
    ];

    $field_type = $field->getType();
    $parent_entity_type = $field->getTargetEntityTypeId();
    $target_entity_type = $field->getSetting('target_type');

    if (in_array($field_type, $allowed_field_types) && in_array($parent_entity_type, $allowed_parent_entity_types) && in_array($target_entity_type, $allowed_target_entity_types)) {
      // Return TRUE if we found a field type/parent entity type that we want.
      return TRUE;
    }
    // Return FALSE if this isn't in our allowed values arrays.
    return FALSE;
  }

  /**
   * Function to return an array of entity type ids that contain view modes.
   *
   * @return array
   *   An array containing all content entities that register view modes, keyed
   *   by entity_id.
   */
  public static function getEntitiesWithViewModes() {
    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $content_entities_with_view_modes = [];
    foreach ($entity_types as $id => $entity_type) {
      if ($entity_type->getGroup() == 'content') {
        $entity_view_modes = \Drupal::entityManager()->getViewModes($id);
        if (count($entity_view_modes) > 0) {
          $content_entities_with_view_modes[$id] = $id;
        }
      }
    }
    return $content_entities_with_view_modes;
  }

  /**
   * Returns the entity labels.
   *
   * @param int $eid
   *   Entity ID to find the entity type for.
   *
   * @return mixed
   *   Array containing the following:
   *   - label
   *   - title
   *   Or false.
   */
  public static function returnEntityLabels($type, $eid) {
    // @todo: Entity::load() Needs urgent refactor/debugging.
    $entityController = \Drupal::entityTypeManager()->getStorage($type);
    $entity = $entityController->load($eid);

    $type = $entity->getEntityTypeId();

    $entity_label = FALSE;
    $entity_title = FALSE;

    switch ($type) {
      case 'node':
        // Label of the Node Type.
        $entity_label = $entity->type->entity->label();
        // Title of the Node.
        $entity_title = $entity->label();
        break;
    }

    if ($entity_label && $entity_title) {
      return [
        'label' => $entity_label,
        'title' => $entity_title,
      ];
    }
    return FALSE;
  }
}
