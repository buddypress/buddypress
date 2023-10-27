# BuddyPress URI Globals Functions

These functions are informing about the BuddyPress object being displayed on front-end. Thanks to them, you can programmatically know whether a component's directory or a component's single item or a specific sub-page of both is being loaded. These are set inside the `parse_query()` method of [Components main classes](./../component/build-component.md#parsing-requests-about-your-custom-add-on-directory) when a BuddyPress page loads according to the requested URL.

**NB**: it's important to note that since version 12.0.0 of BuddyPress, URI globals returned by the following functions are only fully set once the `bp_parse_query` hook has been fired. 

## URL schemas

To make sure to use the BP URI globals the right way, you need to know about the specificities of BP URL schemas.

| Contexts | Schemas |
|---|---|
| Directories | site.url/`bp_current_component()`/ |
| Members single item | site.url/members-slug/`bp_current_item()`/`bp_current_component()`/`bp_current_action()`/`bp_action_variables()`/ |
| Groups single item | site.url/groups-slug/`bp_current_item()`/`bp_current_action()`/`bp_action_variables()`/ |

## `bp_current_item()`

This function was introduced in verion 1.0.0 & returns the slug of the single item being viewed. For instance, in `site.url/members/buddypress/` or `site.url/groups/buddypress/`, `bp_current_item()` is `buddypress`. 

## `bp_current_component()`

This function was introduced in verion 1.0.0 & returns the slug of the components being viewed. For instance, in `site.url/members/buddypress/activity/` or `site.url/activity/`, `bp_current_component()` is `activity`.

## `bp_current_action()`

This function was introduced in verion 1.0.0 & returns the slug of the single item action being viewed. For instance, in `site.url/members/buddypress/activity/mentions` or `site.url/groups/members`, `bp_current_component()` is respectively `mentions` & `members`.

## `bp_action_variables()`

This function was introduced in verion 1.0.0 & returns a list (`array`) of all what's after the single item action slug of the requested URL. For instance, in `site.url/members/buddypress/profile/edit/group/1/`, `bp_action_variables()` are `array( 'group', 1 )`.
