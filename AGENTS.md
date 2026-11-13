# BuddyPress Repository Guidance

Use this file to find the right repository, source files, and checks before changing BuddyPress. Detailed procedures live in the linked guides. If guidance conflicts with the target branch or a maintainer's decision, report the conflict before acting.

## Repository Authority

| System | Purpose |
| --- | --- |
| [Development SVN](https://buddypress.svn.wordpress.org/) | Canonical source, development trunk, maintenance branches, and release tags. |
| [GitHub mirror](https://github.com/buddypress/buddypress) | Git checkout and code review. Do not merge pull requests here or push to upstream mirror branches or tags. |
| [BuddyPress Trac](https://buddypress.trac.wordpress.org/) | Tickets, patches, milestones, and project decisions. Keep discussion here. |
| [WordPress.org plugin SVN](https://plugins.svn.wordpress.org/buddypress/) | Release deployment, not a development checkout. Changes here can reach users. |

Read the ticket and its discussion before editing. Confirm the problem, milestone, target branch, and expected behavior. For patch-based contributions, attach the patch to Trac. Alternatively, use a GitHub pull request linked to the Trac ticket for code review only; no separate patch attachment is required. Keep project discussion on Trac, as explained in the [PR template](.github/pull_request_template.md) and [code contribution guide](docs/contributor/code/README.md).

## Safety and Scope

- Inspect the working tree and preserve unrelated changes. Do not reset, revert, delete, or overwrite other work to make a task easier.
- Keep changes limited to the agreed task. Separate unrelated cleanup, dependency updates, and security fixes.
- When acting on someone else's behalf, do not stage, commit, push, create public tickets or pull requests, upload patches, or publish releases without explicit authorization. Permission to edit locally is not permission to publish.
- Do not modify deployment SVN, stable tags, release tags, or release metadata during ordinary development. Published release tags must not be edited or removed.
- Respect local environment instructions and permission limits. Do not change services, install dependencies, or reset databases without approval. Never use a live site's database for tests.
- Keep credentials, private configuration, personal data, and embargoed security details out of patches, logs, tickets, and reports.

## Working Tree

| Path | What belongs here |
| --- | --- |
| `src/` | Plugin source, component PHP, templates, and assets. |
| `src/js/` | JavaScript sources and build configuration for blocks and admin assets. |
| `build/` | Generated distribution files. Do not hand-edit these as a source fix. |
| `bp-loader.php` | Development loader that selects source or build files. |
| `tests/phpunit/` | Test bootstrap, helpers, fixtures, and regression tests. |
| `docs/` | Contributor and developer documentation. |
| `.github/workflows/` | Current CI checks and test matrix. |
| `Gruntfile.js`, `package.json`, `composer.json` | Build tasks, scripts, and dependency requirements. |

The root loader uses `build/bp-loader.php` when it exists, unless `BP_LOAD_SOURCE` is defined. A stale build can therefore hide source changes. Check which tree is loaded before debugging. Use the supported source-loading option in an approved development environment rather than deleting a build or changing managed configuration without permission.

Some assets inside `src/` are also generated. Edit their JavaScript or Sass sources and use the matching build task when required. Inspect the resulting diff so generated changes do not hide unrelated edits.

## Branch and Version Targets

- Use development trunk for the next release; the Git mirror uses `master`. Maintenance work belongs on the branch for the affected release line.
- Confirm the supported target with the ticket, [Trac roadmap](https://buddypress.trac.wordpress.org/roadmap), and maintainer instructions. Do not infer support from the existence of a branch.
- A trunk fix does not prove that a maintenance release contains it. Check each requested backport and test it against that branch's code and requirements.
- Treat tags as release snapshots, not working branches. Version bumps and tagging follow the [release checklist](docs/contributor/project/release/build-checklist.md), not an ordinary bug-fix workflow.

### REST API Versions

[REST API v2](docs/developer/execution-contexts/rest-api/README.md) was introduced in BuddyPress 15.0.0, which also deprecated v1. The separate, archived [BP-REST repository](https://github.com/buddypress/BP-REST) provides the v1 plugin and links to its documentation.

For older releases or v1 fixes, inspect the target branch, its SVN external configuration, and the separate implementation before choosing where to edit. Do not assume a v2 change fixes v1 or can be copied unchanged into an older release. Confirm the intended repository and backport route with maintainers when ownership is unclear.

## Standards and Compatibility

Follow the [code contribution guide](docs/contributor/code/README.md), [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/), and [inline documentation standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/). The repository's [PHPCS configuration](phpcs.xml.dist) applies the BuddyPress ruleset.

Preserve public hooks, hook arguments, return shapes, capability checks, and behavior that themes or plugins depend on unless the task explicitly changes them. Follow nearby component conventions. Check the target branch's PHP and WordPress requirements rather than relying on the versions installed locally.

## Checks Matched to the Change

Read the task definitions before running commands. The [contribution guide](docs/contributor/code/README.md) describes setup and the default `wp-env` environment. Use an existing approved runner when the local environment provides one; do not replace its safeguards.

| Change | Relevant checks |
| --- | --- |
| Documentation only | Check paths, links, factual claims, and patch whitespace. |
| PHP behavior | Add or update regression tests; run focused tests and the relevant wider suite. Check coding standards and compatibility. |
| Site or network behavior | Test the affected single-site and multisite paths. |
| Cache-sensitive behavior | Test with and without a persistent object cache, including the relevant Redis or Memcached CI cases. Check invalidation as well as cache hits. |
| JavaScript, CSS, or build inputs | Run the matching lint and asset tasks, inspect generated output, and check affected behavior in the browser. |
| Release packaging | Build and inspect the distribution, then follow the release testing checklist. |

Available commands, after the required test environment and dependencies are approved and configured:

- `composer test` and `composer test_multi`: single-site and multisite PHPUnit suites.
- `npm run test-php` and `npm run test-php-multisite`: the corresponding suites in the default `wp-env` environment.
- `composer phpcs`, `composer phpcs-escape`, and `composer phpcompat`: coding standards, output escaping, and PHP compatibility checks.
- `grunt jstest`: the JavaScript validation and JSHint tasks defined in `Gruntfile.js`. Block and admin sources have separate scripts in `package.json`.

The [unit-test workflow](.github/workflows/unit-test-object.yml) defines the current PHP, WordPress, multisite, and object-cache matrix. The [coding-standards workflow](.github/workflows/coding-standards.yml) defines the current static checks. Use those files rather than a copied version matrix. Report checks that cannot run; do not claim they passed or bypass failed gates.

## Releases and Deployment

Follow the [release build checklist](docs/contributor/project/release/build-checklist.md) and [release test checklist](docs/contributor/project/release/test-checklist.md) only when release work is explicitly authorized.

Development source and deployment files are not a one-to-one copy. `grunt build` produces the distribution in `build/`; it also runs asset and validation tasks, replaces build output, and downloads release tooling. Inspect its definition and prerequisites before running it. A build is not a deployment.

Deploy only the reviewed distribution to the intended WordPress.org trunk, branch, or tag. An older-release backport must not change the stable tag for the current release. Verify the public download and its contents after an authorized deployment; a source commit alone does not prove users received the package.

## Security

Follow [SECURITY.md](SECURITY.md) to report vulnerabilities through the WordPress HackerOne program. Do not disclose an unreported or embargoed vulnerability in a public Trac ticket, PR, branch, or artifact. Keep security details private and follow the reporting program's disclosure guidance.

## Completion and Maintenance

Before reporting completion, review the full diff and run the applicable checks. State what changed, the exact checks and results, what was not tested, and any remaining risks or decisions. For a documentation-only patch, verify links and run a whitespace check such as `git diff --check`; also check newly added files that are not yet tracked.

When repository behavior or workflows change, flag stale guidance and update the relevant section as part of an approved change. Prefer links to maintained procedures over copying them here. Keep handbook edits independently reviewable from unrelated functional or security work.
