# Building a new BuddyPress release

Releasing BuddyPress to the world is a big deal, and takes quite a few manual steps to ensure safe & comfortable updates & upgrades are had by all. Below are the steps release leads go through with each & every release.

Note: These steps vary slightly depending on major/minor/urgency of the deployment to WordPress.org. For questions, or if something is not clear, please ping [@JJJ](https://profiles.wordpress.org/johnjamesjacoby), [@boone](https://profiles.wordpress.org/boonebgorges/), [@djpaul](https://profiles.wordpress.org/djpaul/) or [@imath](https://profiles.wordpress.org/imath/) on [Slack](https://wordpress.slack.com/messages/buddypress). They are mad enough to comprehend how & why all this works the way it does. (We keep planning to automate this, but until then, please enjoy this extremely long and intimidating list of responsibilities.)

## Prologue
There are two code repositories, and you’ll be tasked with compiling the BuddyPress codebase from the development repo to the deployment repo. They are separate because BuddyPress is deployed to almost all end-users from the public WordPress.org plugin repository in a way that requires some development assets to be pre-compiled (it’s not a literal 1-to-1 copy) and because BuddyPress is lucky enough to have its own Trac instance on WordPress.org.

> [!IMPORTANT]
> We’ll refer to these two repositories like this for the duration of these steps:

`[dev]`   = `buddypress.svn.wordpress.org`
`[wporg]` = `plugins.svn.wordpress.org/buddypress/`

Now that you’re familiar, sit back, relax, put on your headgear, and get ready for a trip to the atmosphere…

## Preliminary tasks

> [!NOTE]
> These tasks are only required for minor/major releases.

1. Create a `Releases` child page on codex called "Version X.Y.Z" so that the permalink looks like `https://codex.buddypress.org/releases/version-1-5-5/`
2. Review **Noteworthy Contributors** and release contributor credits (props). Change as needed.
3. If it's a major release, don't forget to find a nice Pizza place to name the release.
4. Draft an announcement post on [BuddyPress.org](https://buddypress.org) like [this one](https://buddypress.org/2023/12/buddypress-12-0-0-nonno/).
5. If this is a major release, check [Trac](https://buddypress.trac.wordpress.org/roadmap) to see whether there are tickets closed against a minor release that will not happen due to the major release. (For example, 3.3.0 was subsumed into 4.0.0.) If so, reassign those tickets to the major release milestone, and delete the unused milestone.

## Version Bumps (in `[dev]`)

> [!IMPORTANT]
> For major releases (eg: 12.0.0) or pre-releases (eg: 12.0.0-beta1 or 12.0.0-RC2), switch to: `/trunk/`.

```bash
svn switch https://buddypress.svn.wordpress.org/trunk/
```

> [!IMPORTANT]
> For minor releases (eg: 12.1.0), switch to relative branch: `branches/12.0/`

```bash
svn switch https://buddypress.svn.wordpress.org/branches/12.0/
```

1. Change version in bp-loader.php (plugin header)
2. Change version in src/bp-loader.php (plugin header)
3. Change $this->version (setup_globals()) in class-buddypress.php
4. Change stable-tag readme.txt (major and minor releases)
6. Change version in package.json
7. Use the latest version of npm and: npm install and then npm shrinkwrap

> [!IMPORTANT]
> The following two steps are only required when releasing a major or minor version.

1. Change tested-up-to readme.txt
2. Add “Upgrade Notice” & “Changelog” entries for this version in readme.txt (major and minor releases)

**Commit changes!**

```bash
svn ci -m 'X.Y.Z version bumps'
```

## Tagging/Branching (in `[dev]`)

> [!CAUTION]
> Once created, a tag cannot be removed or edited nor trunk merged to it so please ensure all necessary updates to trunk are committed before creating the tag copy from it.

### For major releases (2.7.0), branch from trunk, then tag from new branch.

```bash
svn cp https://buddypress.svn.wordpress.org/trunk/ https://buddypress.svn.wordpress.org/branches/12.0
svn cp https://buddypress.svn.wordpress.org/branches/12.0 https://buddypress.svn.wordpress.org/tags/12.0.0
```

### For minor releases (12.1.0), tag from relevant branch.

```bash
svn cp https://buddypress.svn.wordpress.org/branches/12.0 https://buddypress.svn.wordpress.org/tags/12.1.0
```

### For Beta and Release Candidate releases (12.0.0-beta1), create tag from trunk.

```bash
svn cp https://buddypress.svn.wordpress.org/trunk https://buddypress.svn.wordpress.org/tags/12.0.0-beta1
```

## Deploying to `[wporg]`

You’ll probably want to checkout the entire BuddyPress repository from WordPress.org into a local directory. This way you can navigate the entire trunk/branches/tags structure, and more easily make changes as necessary.

```bash
mkdir buddypress-wporg-repo
cd buddypress-wporg-repo
svn co https://plugins.svn.wordpress.org/buddypress/ . --ignore-externals
```

Now you can export the development version of BuddyPress you intend to deploy to users:

```bash
mkdir buddypress-to-deploy
cd buddypress-to-deploy
svn co https://buddypress.svn.wordpress.org/tags/12.0.0/ . --force --ignore-externals.
```

Use the latest version of npm and run: `npm install` and then `composer install && grunt build`. (This part can take quite a long time 🧘)

> [!IMPORTANT]
> If it's a major or minor release: control everything went fine.

+ Copy the content of the `buddypress-to-deploy/build` folder into a new `buddypress` folder.
+ Zip it.
+ Test on a WordPress fresh install there are no problems installing this new version of BuddyPress.
+ Test on a WordPress install already having a previous version of BuddyPress installed and use the WordPress Plugin's Add new screen to check there are no problems upgrading to this new version of BuddyPress.

> [!TIP]
> If you organized a release party on BuddyPress Slack's channel, don't hesitate to ask other contributors to help you test this new version.

+ If lights are green, overwrite the contents of the trunk directory with the contents of `build` in the [wporg] checkout.
+ If it’s a beta or a release candidate, make sure the Stable tag in both trunk and the newly created tag are the same and are the one of current stable version of BuddyPress.
+ Run `svn stat` to check if you need to`svn add` or `svn delete` files.
+ Create an svn tag from `trunk` using `svn cp trunk tags/12.0.0`.
+ If it’s **not a beta nor a release candidate**, make sure the Stable tag in both trunk and the newly created tag are the same and are the one of the new version of BuddyPress. [This is needed to make sure GlotPress will successfully update Translation strings](https://meta.trac.wordpress.org/ticket/4752#comment:1).
+ Commit & 🤞!

```bash
svn ci -m 'Update trunk with X.Y.Z code & create X.Y.Z tag from trunk'
```

> [!IMPORTANT]
> If it's a major or minor release: Control everything went fine.

+ Go to [BuddyPress WP Plugin directory page](https://wordpress.org/plugins/buddypress/)
+ You may need to wait a few minutes, but you should see the main "Download" button of this page is now set to the new version you deployed and the `Version:` information into the right sidebar should be set to your new stable tag.
+ Download the Zip file and check every BuddyPress folders are included.

> [!TIP]
> Using a tool like [DiffMerge](https://sourcegear.com/diffmerge/) can save you some time when checking the content of the downloaded Zip file is the same than your `buddypress-to-deploy/build` folder.

## Announcements

1. Publish the announcement post on [BuddyPress.org](https://buddypress.org)
2. Open a new support "super sticky" topic in [buddypress.org/support](https://buddypress.org/support/) to share the great news with the community and let people eventually ask for support from there.
3. Write a post on the [BP Team's blog](https://bpdevel.wordpress.com) to share the news with our subscribers.

## Trac cleanup

- If any tickets remain in milestone, close them or reassign them to a future milestone.
- Close milestone.
- From Trac Admin, ensure that milestone exists for next major and minor releases.
- From Trac Admin, create a Version for the completed release.

## Version Bumps (in `[dev]`)

- If it's a major x.0.0 release, bump trunk version numbers to alpha in bp-loader.php (Y.0.0-alpha).
- Bump relevant branch version numbers to alpha in bp-loader.php (x.1.0-alpha).
- If it's a major or minor release, update `[dev]` trunk's `readme.txt` stable tag and Upgrade/Changelog informations using the `[wporg]` trunk's ones.

🏁 Release built! Great job 👏
