# Site Health Screen
> [!TIP]
> Detailed information regarding the **Site Health** screen can be found at [Support guides/Dashboard/Site Health screen](https://wordpress.org/documentation/article/site-health-screen/).

BuddyPress, currently, adds 2 (two) accordion panels to the **[Site Health Info](https://wordpress.org/documentation/article/site-health-screen/#info)** tab:

1. **[BuddyPress](#1-buddypress)**
2. **[BuddyPress Constants](#2-buddypress-constants)**
> [!NOTE]
> This information may prove instrumental in resolving site issues while communicating with support personnel, e.g., use the **Copy site info to clipboard** button and include any relevant information that will augment your support request.
***   
![site health screen example](assets/site-health-screen.png)
An example of the **Site Health** screen

### (1) BuddyPress
This panel contains details about your BuddyPress configuration depending upon which components are enabled.

![site health - BuddyPress panel](assets/shs-buddypress-panel-02.png)

The image above is an example of an expanded BuddyPress panel with minimum components enabled. Below is the full list of attributes and the component associated with each configurable attribute.

> [!NOTE]
> The **\*** (asterisk) indicates whether the **BuddyPress standalone theme** or the **Active template pack** attribute will be shown (not both). If no **BuddyPress standalone theme** is detected, then the fallback is to show the **Active template pack** attribute.
>
> Additionally, plugins may add **optional components** to the list of **Active components** attribute and/or add additional attributes.

| Attribute | Description | Component |
|:-------|:--------|:-------|
| Version | The installed version of BuddyPress. | BuddyPress Core |
| Active components | A list of active components. The defaults are **BuddyPress Core**, **Community Members**, **Extended Profiles**, **Account Settings**, **Activity Streams** & **Notifications**. Configurable from [BuddyPress Components](administration/settings/components.md) screen, with the exceptions of **BuddyPress Core** & **Community Members** which are **Must-Use** components. | BuddyPress Core |
| URL Parser | Indicates which URL Parser is in use. The default is **BP Rewrites API**. Can be changed to the **Legacy Parser** by installing the [BP Classic](https://wordpress.org/plugins/bp-classic/) add-on. | BuddyPress Core |
| Community visibility | Indicates whether the BuddyPress community is public (**Anyone**) or private (**Members Only**). The Default is public (**Anyone**). Configurable from the [BuddyPress Options](administration/settings/options.md#community-visibility) screen. | BuddyPress Core |
| BuddyPress standalone theme\* | Indicates which BuddyPress standalone theme is in use. | BuddyPress Core |
| Active template pack\* | Indicates which BuddyPress template pack is in use. The Default is **BuddyPress Nouveau**. Configurable from the [BuddyPress Options](administration/settings/options.md#template-pack) screen. | BuddyPress Core |
| Toolbar | Indicates whether the WordPress **Toolbar** is shown on the front-end for **logged out** users. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#toolbar) screen. See [Toolbar](https://wordpress.org/documentation/article/toolbar/) for additional information. | BuddyPress Core |
| Account Deletion | Indicates whether registered members are allowed to delete their own accounts. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#account-deletion) screen. | Account Settings |
| Community Members: Profile Photo Uploads | Indicates whether registered members are allowed to upload avatars. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#profile-photo-upload) screen. | Community Members |
| Community Members: Cover Image Uploads | Indicates whether registered members are allowed to upload cover images. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#cover-image-upload) screen. | Community Members |
| Community Members: Invitations | Indicates whether registered members are allowed to invite people to join the network. The Default is **disabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#invitations) screen. | Community Members |
| Community Members: Membership Requests | Indicates whether visitors are allowed to request site membership. If enabled, an administrator must approve each new site membership request. The Default is **disabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#membership-requests) screen. ***Note**: The "**Anyone can register**" checkbox must be **disabled** in order to **enable** this feature (see [General Settings - Membership](https://wordpress.org/documentation/article/settings-general-screen/#membership) for where to **enable** or **disable** the  "**Anyone can register**" checkbox)* | Community Members |
| Extended Profiles: Profile Syncing | Indicates whether BuddyPress to WordPress profile syncing is allowed. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#profile-syncing) screen. | Extended Profiles |
| User Groups: Group Creation | Indicates whether group creation for all users is allowed. Administrators can always create groups, regardless of this setting. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#group-creation) screen. | User Groups |
| User Groups: Group Photo Uploads | Indicates whether customizable avatars for groups is allowed. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#group-photo-upload) screen. | User Groups |
| User Groups: Group Cover Image Uploads | Indicates whether customizable cover images for groups is allowed. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#group-cover-image-upload) screen. | User Groups |
| User Groups: Group Activity Deletions | Indicates whether group administrators and moderators to delete activity items from their group's activity stream is allowed. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md) screen. | User Groups |
| Activity Streams: Post Comments | Indicates whether activity stream commenting on posts and comments is allowed. The Default is **disabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#post-comments) screen. ***Note**: The Site Tracking component must be **active** in order to **enable** this feature*. |Activity Streams & Site Tracking |
| Activity Streams: Activity auto-refresh | Indicates whether a check for new items while viewing the activity stream is automatically allowed. The Default is **enabled**. Configurable from the [BuddyPress Options](administration/settings/options.md#activity-auto-refresh) screen. |Activity Streams |

### (2) BuddyPress Constants

![site health - BuddyPress panel](assets/shs-buddypress-constants-03.png)

The image above is a truncated example of an expanded BuddyPress Constants panel. Below is the full list of BuddyPress Constants and their associated default values.

> [!Note]
> *A constant is an identifier (name) for a simple value. As the name suggests, that value cannot change during the execution of the script (except for magic constants, which aren't actually constants). Constants are case-sensitive. By convention, constant identifiers are always uppercase*. [PHP Manual - Constants](https://www.php.net/manual/en/language.constants.php)

Some of these Constants are user-definable and will alter the behavior of BuddyPress when the defaults are overridden. This list is made available for your purvey in the event a misdefined Constant may be causing unexpected behavior.

| Constant | Default Value |
| :------- | :------------ |
| BP_VERSION | The installed version of BuddyPress. |
| BP_DB_VERSION | The installed database version of BuddyPress. |
| BP_REQUIRED_PHP_VERSION | The minimum supported PHP version. |
| BP_PLUGIN_DIR | The filesystem directory path (with trailing slash) for the BuddyPress plugin. |
| BP_PLUGIN_URL | The URL directory path (with trailing slash) for the BuddyPress plugin. |
| BP_IGNORE_DEPRECATED | undefined |
| BP_LOAD_DEPRECATED | undefined |
| BP_ROOT_BLOG | 1 (integer) |
| BP_ENABLE_MULTIBLOG | undefined |
| BP_ENABLE_ROOT_PROFILES | undefined |
| BP_DEFAULT_COMPONENT | undefined |
| BP_XPROFILE_BASE_GROUP_NAME | If **Extended Profiles** component is active, this value will be the label of the primary group of xProfile fields (see [BuddyPress xProfile fields administration](administration/users/xprofile.md)). Otherwise, the value is undefined. |
| BP_XPROFILE_FULLNAME_FIELD_NAME | If **Extended Profiles** component is active, this value will be the label of the primary xprofile field (see [BuddyPress xProfile fields administration](administration/users/xprofile.md)). Otherwise, the value is undefined. |
| BP_MESSAGES_AUTOCOMPLETE_ALL | undefined |
| BP_DISABLE_AUTO_GROUP_JOIN | undefined |
| BP_GROUPS_DEFAULT_EXTENSION | undefined |
| BP_MEMBERS_REQUIRED_PASSWORD_STRENGTH | undefined |
| BP_EMBED_DISABLE_PRIVATE_MESSAGES | undefined |
| BP_EMBED_DISABLE_ACTIVITY | undefined |
| BP_EMBED_DISABLE_ACTIVITY_REPLIES | undefined |
| BP_ENABLE_USERNAME_COMPATIBILITY_MODE | undefined |
| BP_AVATAR_DEFAULT_THUMB | undefined |
| BP_AVATAR_DEFAULT | undefined |
| BP_AVATAR_URL | The URL directory path for Avatar uploads. |
| BP_AVATAR_UPLOAD_PATH | The filesystem directory path for Avatar uploads. |
| BP_SHOW_AVATARS | 1 (integer) |
| BP_AVATAR_ORIGINAL_MAX_WIDTH | 450 (pixels) |
| BP_AVATAR_ORIGINAL_MAX_FILESIZE | 5120000 (bits) |
| BP_AVATAR_FULL_HEIGHT | 150 (pixels) |
| BP_AVATAR_FULL_WIDTH | 150 (pixels) |
| BP_AVATAR_THUMB_HEIGHT | 50 (pixels) |
| BP_AVATAR_THUMB_WIDTH | 50 (pixels) |
| BP_FORUMS_PARENT_FORUM_ID | 1 (integer) |
| BP_FORUMS_SLUG | forums (string) |
| BP_SEARCH_SLUG | search (string) |
| BP_SIGNUPS_SKIP_USER_CREATION (deprecated)[^1] | undefined |
| BP_USE_WP_ADMIN_BAR (deprecated)[^1] | undefined |
| BP_FRIENDS_DB_VERSION (deprecated)[^1] | undefined |
| BP_MEMBERS_SLUG (deprecated)[^1] | undefined |
| BP_GROUPS_SLUG (deprecated)[^1] | undefined |
| BP_MESSAGES_SLUG (deprecated)[^1] | undefined |
| BP_NOTIFICATIONS_SLUG (deprecated)[^1] | undefined |
| BP_BLOGS_SLUG (deprecated)[^1] | undefined |
| BP_FRIENDS_SLUG (deprecated)[^1] | undefined |
| BP_ACTIVITY_SLUG (deprecated)[^1] | undefined |
| BP_SETTINGS_SLUG (deprecated)[^1] | undefined |
| BP_XPROFILE_SLUG (deprecated)[^1] | undefined |
### Footnotes
[^1]: Deprecated, a "**doing it wrong**" error notice is cast.
