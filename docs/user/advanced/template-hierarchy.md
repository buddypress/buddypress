# Customizing templates

BuddyPress follows a template hierarchy system that allows you to customize the appearance of your community site. This document will guide you through the basics of BuddyPress template hierarchy, how to customize templates, and the structure of BuddyPress template files.

The way you can override default BuddyPress templates with your custom templates has evolved since the first versions of the plugin when a specific and standalone BuddyPress theme was required to display your community content (until version 1.7).

The current (and most common) way to achieve template overrides is based on the BP Theme Compatibility API and the BP Template Packs which made BuddyPress content integration available in all WordPress themes (even Block only ones!).

## BP Template Packs template hierarchy

BuddyPress comes with 2 template packs. By default, the BP Nouveau template pack is active but you can switch back to the BP Legacy one from the "Options" tab of your dashboard's `Settings > BuddyPress` area.

1. `buddypress\bp-templates\bp-legacy`: This folder contains the BP Legacy templates: the ones that were created out of the BP Default standalone theme (deprecated).
2. `buddypress\bp-templates\bp-nouveau`: this folder contains the BP Nouveau templates which brought an improved design and dynamic UIs for your community area.

### Template Loading Order

When BuddyPress loads a template, it follows this order:

1. **Child Theme**: BuddyPress looks for templates in the child theme directory first. This allows you to customize templates without modifying the parent theme.
2. **Parent Theme**: If the template is not found in the child theme, BuddyPress will look in the parent theme directory.
3. **BP active template pack**: If neither the child nor the parent theme contains the template, BuddyPress will use its own templates (`bp-legacy` or `bp-nouveau`).

### The BuddyPress base template file

The BP Theme Compatibility API is first looking for the best based template file within the active theme, it then picks it to use its `the_content()` template tag as a placeholder to insert what are actually **template parts** from the active BP Template Pack directory.

1. `active-wordpress-theme/plugin-buddypress.php`,
2. `active-wordpress-theme/buddypress.php`,
3. `active-wordpress-theme/community.php`,
4. `active-wordpress-theme/generic.php`,
5. `active-wordpress-theme/page.php` <- most commonly picked,
6. `active-wordpress-theme/single.php`,
7. `active-wordpress-theme/singular.php`,
8. `active-wordpress-theme/index.php`.

The `page.php` template is the one that is generally picked: that's because themes including the 4 first ones are not very common. But if you add a new template to the active theme's directory having one of the 4 first file names then it will be the one BuddyPress will pick. That's how you can customize the base template file BuddyPress should use.

> [!TIP]
> An easy way to customize the base template file is to copy the `page.php` template of your active WordPress theme, rename it `buddypress.php`, remove the unused comment template tags and fine tune your HTML markup from there!

### Customizing BuddyPress Template parts

To customize a BuddyPress template part:

1. **Copy the Template part**: Locate the template file in the BuddyPress plugin directory (either `bp-legacy` or `bp-nouveau`) and copy it to your theme's directory. For example, copy `activity/index.php` from `bp-legacy` to your theme's `buddypress` or `community` folder (`wp-content/themes/your-theme/buddypress/activity/index.php`).

2. **Modify the Template part**: Edit the copied template file in your theme directory as needed. Your changes will override the default BuddyPress template.

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
- **groups/**: Templates for user groups
- **members/**: Templates for member profiles and directories

### Directory Structure for Overriding BuddyPress Legacy Templates

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

### Directory Structure for Overriding BuddyPress Nouveau Templates

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

## Standalone BuddyPress theme template hierarchy

TBD.

## BuddyPress Block only theme template hierarchy

TBD.
