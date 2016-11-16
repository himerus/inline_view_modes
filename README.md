# Inline View Modes 

## Purpose

The **Inline View Modes** module should account for the following functionality.

When placing a normal entity reference field on any entity (node, paragraph, taxonomy term, etc.), 
the ability to choose a view mode by a content editor with appropriate permissions for the referenced entity should be allowed.

Initial configuration Handled through configuration of Entity Reference Field


## Field Configuration Options
**Enable View mode selection PER reference**
> Each referenced entity in a multiple value field can select a different view mode

**Enable View mode selection PER field.** 
> Each "group" of referenced entities must use the same view mode.
  This should only be used on entity reference fields that filter to allow a single entity type to be referenced.

## Form Display Options
* None?

## Display Options
* Allow some forced override of the settings??

## Moderation Options
> Any moderation options should be handled the same as an Entity Reference field. 

## Permissions
* `administer inline view modes`
`use inline view modes`

## Modules to integrate
* Entity Reference Revisions
