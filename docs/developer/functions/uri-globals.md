# BuddyPress URI Globals Functions

These functions provide information about the BuddyPress object being displayed on the front end. Thanks to them, you can programmatically know whether a component's directory, a component's single item, or a specific sub-page is being loaded. These globals are set inside the `parse_query()` method of the [main class](./../component/build-component.md#parsing-requests-about-your-custom-add-on-directory) of each component when a BuddyPress page loads.

**NB**: It's important to note that as of version 12.0.0 of BuddyPress, URI globals  are only fully set once the `bp_parse_query` hook has been fired. 

## URL schemas

To use the BP URI globals the right way, you need to know about the specificities of the BP URL schemas.

| Context | Schema |
|---|---|
| Directories | site.url/`bp_current_component()`/ |
| Members single item | site.url/members-slug/`bp_current_item()`/`bp_current_component()`/`bp_current_action()`/`bp_action_variables()`/ |
| Groups single item | site.url/groups-slug/`bp_current_item()`/`bp_current_action()`/`bp_action_variables()`/ |

## `bp_current_item()`

This function was introduced in version 1.0.0. It returns the slug of the single item being viewed. For instance, in `site.url/members/alpha/` or `site.url/groups/alpha/`, `bp_current_item()` returns `alpha`. 

## `bp_current_component()`

This function was introduced in version 1.0.0. It returns the slug of the component being viewed. For instance, in `site.url/members/marie/activity/` or `site.url/activity/`, `bp_current_component()` returns `activity`.

## `bp_current_action()`

This function was introduced in version 1.0.0. It returns the slug of the single item action being viewed. For instance, in `site.url/members/marie/activity/mentions`, `bp_current_action()` returns `mentions`; in `site.url/groups/members`, `bp_current_action()` returns `members`.

## `bp_action_variables()`

This function was introduced in version 1.0.0. It returns a list (`array`) of the modifiers that follow the single item action slug in the requested URL. For instance, in `site.url/members/marie/profile/edit/group/1/`, `bp_action_variables()` returns `array( 'group', 1 )`.
