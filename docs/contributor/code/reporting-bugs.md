# Reporting Bugs

The BuddyPress community relies on contributions from people like you to grow stronger every day. As more users test and use BuddyPress, we receive more bug reports, feature suggestions, and enhancement ideas. The BuddyPress Team need these feedbacks to carry on improving the software.

This guide provides helpful information on how to report bugs and features and how you can help fix them.

## Overview of Bug Reporting and Resolution

1. **Identifying a Bug**: A user finds a bug in the core of BuddyPress (not in a Theme or Plugin).
2. **Verifying the Bug**: The user verifies that it is a bug, potentially by posting on our forums.
3. **Reporting the Bug**: If confirmed as a bug, the user submits a ticket to Trac (the BuddyPress bug tracking system).
4. **Bug Confirmation**: A BuddyPress developer confirms the bug and marks it as valid.
5. **Fixing the Bug**: A developer (potentially you) fixes the bug, creates patch files, and uploads them to Trac.
6. **Patch Review**: Other developers review the patch to ensure it fixes the bug without causing other issues.
7. **Patch Commitment**: A BuddyPress core developer commits the patch to the core code for inclusion in the next release.

## Detailed Steps for Bug Reporting and Resolution

### Before You Report a Bug

1. **Make sure it's a BuddyPress issue**: Deactivate all your plugins but BuddyPress, switch to one of the WordPress bundled themes (Twenty Twenty-{Year}). If you added files into the `/wp-content/mu-plugins` folder or added a `wp-content/plugins/bp-custom.php` file: remove them temporarily. Check you can still reproduce the bug.
2. **Search for Existing Reports**: Ensure the bug still needs to be reported by searching Trac.

   - If found, do not report a duplicate. Instead, add any additional information to the existing report.
   - If the issue is similar but not identical, decide whether to add a note to the existing issue or report a new one.
2. **Discussion**: If unsure whether it’s a bug, discuss it on the BuddyPress Support Forum or the `#buddypress` Slack channel.

### Reporting a Bug

BuddyPress uses the same tool WordPress is using to manage its code source as well as bugs reporting. We call it "Trac" and it's available at this URL:
[https://buddypress.trac.wordpress.org/](https://buddypress.trac.wordpress.org/)

You can read this [WordPress contributor documentation page](https://make.wordpress.org/core/handbook/contribute/trac/keywords/#status-based-keywords) for more details about it.

1. **Log into Trac**: Use your BuddyPress forum username and password to log into Trac and select "New Ticket".
2. **Fill in the Ticket Fields**:
   - **Short Summary**: Provide a concise and informative title.
   - **Full Description**: Describe the problem, steps to reproduce it, and include any relevant URLs or screenshots. To provide detailed environment information, you can use the [Test Reports plugin](https://wordpress.org/plugins/test-reports/) or have a look at the debug information provided by the BuddyPress panels of the Tools / Site Health / information section of your WordPress Dashboard.
   - **Priority**: Leave this to default; developers will rank the bug’s priority.
   - **Assign to**: Optionally, take responsibility for the bug by entering your username.
   - **Milestone**: Do not change this; a BuddyPress developer will set it.
   - **Keywords**: Identify affected areas and use standard keywords to flag the bug’s status.
   - **CC**: Enter your email to receive updates on the bug. Reporters are automatically notified.
  - **Attachments**: You can use this section to upload screenshots or [submit a patch](https://github.com/buddypress/buddypress/tree/master/docs/contributor/code#suggesting-a-fix).

### Trac Keywords

The following list is not exhaustive, but it gives you a good idea of what means most commonly used keywords. You can also read about the [Keywords WordPress is using](https://make.wordpress.org/core/handbook/contribute/trac/keywords/#status-based-keywords) as we also use most of them.

- **reporter-feedback**: A response is needed from **You** the reporter.
- **has-patch**: A solution has been attached and is ready for review.
- **needs-testing**: The solution requires testing.
- **2nd-opinion**: Another opinion is needed on the problem or solution.
- **dev-feedback**: A developer’s response is requested (less commonly used).
- **tested**: The patch has been tested; include the patch filename, testing method, and BuddyPress version.
- **needs-patch**: The ticket needs a patch or the submitted patch needs revision.

### Finding Bugs to Fix

Refer to the [Available Reports](https://buddypress.trac.wordpress.org/report/) for links to Trac reports showing which bugs need fixing.

## Joining the BuddyPress Slack Channel

To discuss bugs and other issues, join the [BuddyPress Slack channel](https://wordpress.slack.com/messages/buddypress) by following these steps:

1. Visit [chat.wordpress.org](https://chat.wordpress.org).
2. Scroll to the section “Joining the WordPress team on Slack”.
3. Click the link that says, “I understand. Let’s get started.”
4. You will be taken to the Slack login webpage.
5. Enter your WordPress.org username as your “email” with the pre-filled subdomain `chat.wordpress.org`.
6. Check your email for a confirmation from Slack. (The email will be automatically forwarded to the email address for your WordPress.org account.)
7. In the confirmation email, click the “Confirm email” link.
8. You will be taken to a Slack webpage to set your password.
9. Add your name and select a new password for your Slack account.
10. You will be logged in to the Making WordPress Slack on your browser.

By following these guidelines, you contribute to maintaining and improving BuddyPress, helping the community to grow and thrive.
