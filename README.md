# ban-hammer
Ban Hammer for phpBB 3.3.x line (was One Click Ban)

Gives moderators a one-click way to ban a user directly from their profile or from the MCP post-approval queue: ban the username, email, and/or IP, delete their posts, private messages, avatar, signature, and profile fields, optionally move them into a group, and optionally report them to Stop Forum Spam. As an alternative to banning outright, a moderator can instead restrict a user into a heavily-limited group for a set time (or permanently), with their original group automatically restored once the restriction expires. A "Ban email domain" action is also available from the MCP approve-details page.

Requires PHP 7.4+ and phpBB 3.3.17+.

## Installation

1. Copy (or clone) this extension to `phpBB/ext/phpbbmodders/banhammer`.
2. In the ACP, go to Customise → Manage Extensions and enable Ban Hammer.
3. Configure it under ACP → Ban Hammer: what a ban deletes, an optional group to move banned users into, an optional group to restrict users into instead of banning, ban length options, and (optionally) a Stop Forum Spam API key.

## Automated testing

We use automated unit tests to prevent regressions. Check out our build below:

[![Tests](https://github.com/phpbbmodders/ban-hammer/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/ban-hammer/actions/workflows/tests.yml)

## Acknowledgments

- The avatar-deletion modernization ([PR #21](https://github.com/phpbbmodders/ban-hammer/pull/21)) is based on a fix by [Rich McGirr](https://github.com/rmcgirr83) in his fork, routing avatar deletion through phpBB's `avatar.manager` service instead of the legacy `avatar_delete()` function.
- Code review, bug fixes, and documentation assisted by [Claude](https://www.anthropic.com/claude).
