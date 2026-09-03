# ban-hammer
Ban Hammer for phpBB 3.3.x line (was One Click Ban)

Gives possibility to ban users directly from their profile. Along with banning you can automatically move the user to a certain group, delete their posts, delete their private messages, delete their avatar, signature, and profile information.

## Automated testing

We use automated unit tests to prevent regressions. Check out our build below:

[![Tests](https://github.com/phpbbmodders/ban-hammer/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/ban-hammer/actions/workflows/tests.yml)

## Acknowledgments

- The avatar-deletion modernization ([PR #21](https://github.com/phpbbmodders/ban-hammer/pull/21)) is based on a fix by [Rich McGirr](https://github.com/rmcgirr83) in his fork, routing avatar deletion through phpBB's `avatar.manager` service instead of the legacy `avatar_delete()` function.
- Code review, bug fixes, and documentation assisted by [Claude](https://www.anthropic.com/claude).
