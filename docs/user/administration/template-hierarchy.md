# BuddyPress Template Hierarchy

BuddyPress follows a template hierarchy system that allows you to customize the appearance and functionality of your community site. This document will guide you through the basics of BuddyPress template hierarchy, how to customize templates, and the structure of BuddyPress template files.

## General Information

BuddyPress is a powerful plugin for WordPress that transforms your website into a fully-featured social network. It includes features like user profiles, activity streams, user groups, and more. The customization of these features is achieved through templates.

### Template Hierarchy Overview

BuddyPress templates are organized in a hierarchy, meaning that the plugin will look for specific templates in a certain order. This hierarchy allows you to override default templates with your custom versions.

### BuddyPress Template Folders

1. **bp-legacy**: This folder contains the default templates used by BuddyPress.
2. **bp-nouveau**: This is the newer template pack with improved design and functionality.

### Template Loading Order

When BuddyPress loads a template, it follows this order:

1. **Child Theme**: BuddyPress looks for templates in the child theme directory first. This allows you to customize templates without modifying the parent theme.
2. **Parent Theme**: If the template is not found in the child theme, BuddyPress will look in the parent theme directory.
3. **BuddyPress Default**: If neither the child nor the parent theme contains the template, BuddyPress will use its default templates.

### Customizing BuddyPress Templates

To customize a BuddyPress template:

1. **Copy the Template**: Locate the template file in the BuddyPress plugin directory (either `bp-legacy` or `bp-nouveau`) and copy it to your theme's directory. For example, copy `activity/index.php` from `bp-legacy` to your theme's `buddypress` folder (`wp-content/themes/your-theme/buddypress/activity/index.php`).

2. **Modify the Template**: Edit the copied template file in your theme directory as needed. Your changes will override the default BuddyPress template.

### Common Template Files

Here are some common BuddyPress template files you might want to customize:

- **Members**: `buddypress/members/index.php`
- **Activity**: `buddypress/activity/index.php`
- **Groups**: `buddypress/groups/index.php`
- **User Profile**: `buddypress/members/single/profile.php`

### Template File Structure

BuddyPress template files are organized into several directories, each corresponding to different components of the plugin:

- **activity/**: Templates for activity streams
- **blogs/**: Templates for site tracking
- **common/**: Shared templates and components
- **forums/**: Templates for bbPress forums
- **groups/**: Templates for user groups
- **members/**: Templates for member profiles and directories
- **messages/**: Templates for private messaging
- **settings/**: Templates for user settings

## Directory Structure for Overriding BuddyPress Legacy Templates

Here is the complete directory structure for all the BuddyPress legacy template files. You can copy the necessary files based on your customization needs and override them inside the child theme at the following path.

```Plain text
your-child-theme/
└── buddypress/
    ├── activity/
    │   ├── activity-loop.php
    │   ├── comment.php
    │   ├── entry.php
    │   ├── index.php
    │   ├── post-form.php
    │   └── single/
    │       └── home.php
    │   └── type-parts/
    │       ├── content-created-group.php
    │       ├── content-friendship-created.php
    │       ├── content-joined-group.php
    │       ├── content-new-avatar.php
    │       ├── content-new-member.php
    │       ├── content-updated-profile.php
    │       └── content.php
    ├── assets/
    │   ├── _attachments/
    │   │   ├── avatars/
    │   │   │   ├── camera.php
    │   │   │   ├── crop.php
    │   │   │   ├── index.php
    │   │   │   └── recycle.php
    │   │   ├── cover-images/
    │   │   │   └── index.php
    │   │   └── uploader.php
    │   ├── emails/
    │   │   └── single-bp-email.php
    │   ├── embeds/
    │   │   ├── activity.php
    │   │   ├── footer.php
    │   │   ├── header-activity.php
    │   │   └── header.php
    │   ├── utils/
    │   │   └── restricted-access-message.php
    │   └── widgets/
    │       ├── dynamic-groups.php
    │       ├── dynamic-members.php
    │       └── friends.php
    ├── blogs/
    │   ├── blogs-loop.php
    │   ├── confirm.php
    │   ├── create.php
    │   └── index.php
    ├── common/
    │   └── search/
    │       └── dir-search-form.php
    ├── groups/
    │   ├── create.php
    │   ├── groups-loop.php
    │   └── index.php
    │   └── single/
    │       ├── activity.php
    │       ├── admin.php
    │       ├── admin/
    │       │   ├── delete-group.php
    │       │   ├── edit-details.php
    │       │   ├── group-avatar.php
    │       │   ├── group-cover-image.php
    │       │   ├── group-settings.php
    │       │   ├── manage-members.php
    │       │   └── membership-requests.php
    │       ├── cover-image-header.php
    │       ├── group-header.php
    │       ├── home.php
    │       ├── invites-loop.php
    │       ├── members.php
    │       ├── plugins.php
    │       ├── request-membership.php
    │       ├── requests-loop.php
    │       └── send-invites.php
    ├── members/
    │   ├── activate.php
    │   ├── index.php
    │   ├── members-loop.php
    │   ├── register.php
    │   └── single/
    │       ├── activity.php
    │       ├── blogs.php
    │       ├── cover-image-header.php
    │       ├── friends.php
    │       ├── friends/
    │       │   └── requests.php
    │       ├── groups.php
    │       ├── groups/
    │       │   └── invites.php
    │       ├── home.php
    │       ├── invitations.php
    │       ├── invitations/
    │       │   ├── invitations-loop.php
    │       │   ├── list-invites.php
    │       │   └── send-invites.php
    │       ├── member-header.php
    │       ├── messages.php
    │       ├── messages/
    │       │   ├── compose.php
    │       │   ├── message.php
    │       │   ├── messages-loop.php
    │       │   ├── notices-loop.php
    │       │   └── single.php
    │       ├── notifications.php
    │       ├── notifications/
    │       │   ├── feedback-no-notifications.php
    │       │   ├── notifications-loop.php
    │       │   ├── read.php
    │       │   └── unread.php
    │       ├── plugins.php
    │       ├── profile.php
    │       ├── profile/
    │       │   ├── change-avatar.php
    │       │   ├── change-cover-image.php
    │       │   ├── edit.php
    │       │   ├── profile-loop.php
    │       │   └── profile-wp.php
    │       └── settings.php
    │       ├── settings/
    │       │   ├── capabilities.php
    │       │   ├── data.php
    │       │   ├── delete-account.php
    │       │   ├── general.php
    │       │   ├── notifications.php
    │       │   └── profile.php
```

## Directory Structure for Overriding BuddyPress Nouveau Templates

Here is the complete directory structure for all the BuddyPress Nouveau template files. Based on your customization needs, you can copy the necessary files.

```Plain Text
your-child-theme/
├── buddypress/
│   ├── activity/
│   │   ├── activity-loop.php
│   │   └── comment-form.php
│   ├── assets/
│   │   ├── emails/
│   │   │   └── single-bp-email.php
│   │   ├── embeds/
│   │   │   ├── activity.php
│   │   │   └── header.php
│   │   └── widgets/
│   │       ├── dynamic-groups.php
│   │       └── dynamic-members.php
│   ├── blogs/
│   │   ├── blogs-loop.php
│   │   └── create.php
│   ├── common/
│   │   ├── filters/
│   │   │   ├── directory-filters.php
│   │   │   └── groups-screens-filters.php
│   │   ├── js-templates/
│   │   │   ├── activity/
│   │   │   │   └── form.php
│   │   │   ├── messages/
│   │   │       └── search-form.php
│   │   ├── nav/
│   │   │   └── directory-nav.php
│   │   ├── notices/
│   │   │   └── template-notices.php
│   │   └── search/
│   │       └── search-form.php
│   ├── groups/
│   │   ├── create.php
│   │   ├── groups-loop.php
│   │   └── single/
│   │       ├── activity.php
│   │       ├── admin/
│   │           ├── delete-group.php
│   │           ├── edit-details.php
│   │           ├── group-avatar.php
│   │           ├── group-cover-image.php
│   │           ├── group-settings.php
│   │           ├── manage-members.php
│   │           └── membership-requests.php
│   │       ├── cover-image-header.php
│   │       ├── default-front.php
│   │       ├── group-header.php
│   │       └── home.php
│   ├── members/
│   │   ├── activate.php
│   │   ├── members-loop.php
│   │   └── single/
│   │       ├── activity.php
│   │       ├── blogs.php
│   │       ├── cover-image-header.php
│   │       ├── default-front.php
│   │       ├── friends/
│   │           ├── requests-loop.php
│   │           └── requests.php
│   │       ├── groups/
│   │           └── invites.php
│   │       ├── home.php
│   │       ├── invitations/
│   │           ├── invitations-loop.php
│   │           ├── list-invites.php
│   │           └── send-invites.php
│   │       ├── member-header.php
│   │       ├── messages.php
│   │       ├── notifications/
│   │           └── notifications-loop.php
│   │       ├── parts/
│   │           ├── item-nav.php
│   │           ├── item-subnav.php
│   │           └── profile-visibility.php
│   │       ├── plugins.php
│   │       ├── profile/
│   │           ├── change-avatar.php
│   │           ├── change-cover-image.php
│   │           ├── edit.php
│   │           ├── profile-loop.php
│   │           └── profile-wp.php
│   │       ├── settings/
│   │           ├── capabilities.php
│   │           ├── data.php
│   │           ├── delete-account.php
│   │           ├── general.php
│   │           ├── group-invites.php
│   │           ├── notifications.php
│   │           └── profile.php
```

### Overriding Template Example
To override activity-loop.php:

Copy `activity-loop.php from wp-content/plugins/buddypress/bp-templates/bp-nouveau/buddypress/activity/activity-loop.php`.
Paste it into `wp-content/themes/your-child-theme/buddypress/activity/activity-loop.php`.
Make your desired changes in the copied activity-loop.php file in your child theme.
