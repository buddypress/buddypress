# Reference

Just like the WordPress REST API, the BP REST API is organized around [REST](https://en.wikipedia.org/wiki/Representational_state_transfer), and is designed to have predictable, resource-oriented URLs and to use HTTP response codes to indicate API errors. The API uses built-in HTTP features, like HTTP authentication and HTTP verbs, which can be understood by off-the-shelf HTTP clients, and supports cross-origin resource sharing to allow you to interact securely with the API from a client-side web application.

The BP REST API uses JSON exclusively as the request and response format, including error responses.

The BP REST API provides public data accessible to any client anonymously, as well as private data only available after [authentication](./README.md#about-authentification). Once authenticated the BP REST API supports most BuddyPress community actions, allowing you to enhance your plugins with more responsive management tools, or build complex single-page applications.

This API reference provides information on the specific endpoints available through the API, their parameters, and their response data format.

## Developer Endpoint Reference

| Resource | Base Route |
| --- | --- |
| [Components](./components.md) | `/buddypress/v2/components` |
| Members | `/buddypress/v2/members` |
| Member Profile Photo | `/buddypress/v2/members/<user_id>/avatar` |
| Member Profile Cover | `/buddypress/v2/members/<user_id>/cover` |
| Member Registration | `/buddypress/v2/signup` |
| Activity | `/buddypress/v2/activity` |
| Extended Profile Groups | `/buddypress/v2/xprofile/groups` |
| Extended Profile Field | `/buddypress/v2/xprofile/fields` |
| Extended Profile Data | `/buddypress/v2/xprofile/<field_id>/data/<user_id>` |
| Friends | `/buddypress/v2/friends` |
| User Groups | `/buddypress/v2/groups` |
| Group Profile Photo | `/buddypress/v2/groups/<group_id>/avatar` |
| Group Profile Cover | `/buddypress/v2/groups/<group_id>/cover` |
| Group Membership | `/buddypress/v2/groups/<group_id>/members` |
| Group Membership Requests | `/buddypress/v2/groups/<group_id>/membership-request` |
| Group Invites | `/buddypress/v2/groups/<group_id>/invites` |
| Private Messaging | `/buddypress/v2/messages` |
| Screen Notifications | `/buddypress/v2/notifications` |
| User Blogs | `/buddypress/v2/blogs` |
| Blog Profile Photo | `/buddypress/v2/blogs/<id>/avatar` |
