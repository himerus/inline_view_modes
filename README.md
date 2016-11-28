# Inline View Modes 

## Purpose

The **Inline View Modes** module should account for the following functionality:

When placing a normal **Entity Reference** or **Entity Reference Revisions** field on any entity (_node_, _paragraph_, _taxonomy term_, etc.), 
the content editor (with appropriate permissions) should have the ability to choose a **View Mode** for the referenced entity or group of entities.

When configuring the FieldFormatter for the **Entity Reference [Revisions]** fields via the *Manage Display* screen for the appropriate entity, 
the user should be able to set a default view mode for **EACH** _Target Type_ bundle.
> _This above scenario is a current limitation (oversight?) in Drupal._ 
When you currently create an **Entity Reference** that allows for multiple types of entity bundles to be referenced (Article, Basic Page),
the View Mode settings allow you to select any view mode for **ALL** types of content. 
This includes view modes that aren't even enabled for the specific content types. 
So the problem exists that you could be referencing two node types that don't share any View Modes other than _Default_.

## Field Configuration Options
Provides functionality to add ThirdPartySetting to appropriate fields for enabling or disabling Inline View Modes on an entity.

The field configuration is handled via the `inline_view_modes_form_field_config_edit_form_alter()` function.

The storage of the configuration is handled via `inline_view_modes_add_enable_config()`.

### Proposed Features
#### Enable View mode selection PER reference
**Status:** _In progress_.
> Each referenced entity in a multiple value field can select a different view mode.

#### Enable View mode selection PER field.
**Status:** _To be developed_.
> Each "group" of referenced entities in a multiple value field must use the same view mode.
  This should only be used on entity reference fields that filter to allow a single entity type to be referenced.

## Form Display
Form display is handled through `inline_view_modes_field_widget_form_alter()` for per reference view modes.

## Display Options
In order to allow multiple FieldTypes and FieldWidgets to use the **Inline View Modes** functionality,
all functionality prior to the FieldFormatter is handled through appropriate alter hooks. 
This allows **Inline View Modes** functionailty on both the core **Entity Reference** field and **Entity Reference Revisions** FieldTypes as well as **Inline Entity Form** FieldWidgets (planned).

In order to handle the full adjustments needed to the FieldFormatter for **Rendered Entity** a class is provided that will be used instead.

`class EntityReferenceIvmEntityFormatter extends EntityReferenceEntityFormatter`

## Moderation Options
> Any moderation options should be handled the same as an Entity Reference field. (To investigate) 

## Permissions
* `administer inline view modes` - Should allow a user with proper administrative permissions to create a reference field, and choose to configure it with **Inline View Modes**.
* `use inline view modes` - Should allow a user with proper content adding/editing permissions to select a custom view modes. Otherwise, the defaults configured in the FieldFormatter would be used.

## Modules to integrate
* Drupal Core Entity Reference fields. **Status:** _In progress_.
* [Entity Reference Revisions](https://www.drupal.org/project/entity_reference_revisions) **Status:** _In progress_.
* [Inline Entity Form](https://www.drupal.org/project/inline_entity_form) **Status:** _To be developed_.
