# Ban Hammer

[![Tests](https://github.com/phpbbmodders/ban-hammer/actions/workflows/tests.yml/badge.svg)](https://github.com/phpbbmodders/ban-hammer/actions/workflows/tests.yml) [![Lint](https://github.com/phpbbmodders/ban-hammer/actions/workflows/lint.yml/badge.svg)](https://github.com/phpbbmodders/ban-hammer/actions/workflows/lint.yml)

Ban a user straight from their profile, with options to clean up their content and report them to Stop Forum Spam.

## Features

- **Ban Hammer** form on member profiles, and on the MCP post-approval queue, for moderators with the right permissions.
- Ban by username, and optionally by email and IP.
- Optionally delete the user's avatar, posts and topics, private messages, signature and profile fields.
- Optionally move banned users into a chosen group.
- Instead of banning, restrict a user to a limited group for a set time (or permanently); their original group comes back automatically when it ends.
- A **Ban email domain** action on the MCP approve-details page.
- Optionally report the user to Stop Forum Spam (API key set in the ACP).

## Requirements

- phpBB 3.3.17 or later
- PHP 7.4 or later

## Installation

1. Copy the extension to `/ext/phpbbmodders/banhammer`
2. In the Administration Control Panel, go to **Customise → Manage extensions**
3. Enable the **Ban Hammer** extension
4. Choose the defaults under **ACP → Extensions → Ban Hammer**

## Contributing

Contributions are welcome!

- **Bug reports**: [Open an issue](https://github.com/phpbbmodders/ban-hammer/issues).
- **Everything else** (questions, feature requests, ideas, general discussion): [Use Discussions](https://github.com/orgs/phpbbmodders/discussions), or the [community forum](https://www.phpbbmodders.com/community/).
- Pull requests are welcome for bug fixes or discussed features.

## Acknowledgments

- Based on the phpBB 3.0 **One Click Ban** MOD by phpbbmodders.net (co-authors Kailey and bonelifer; contributors EXreaction, RMcGirr83, Sniper_E and tumba25).
- Converted to a phpBB extension by Rich McGirr ([RMcGirr83](https://github.com/rmcgirr83)) and Jari Kanerva (tumba25).
- The avatar-deletion modernization ([PR #21](https://github.com/phpbbmodders/ban-hammer/pull/21)) is based on a fix by [Rich McGirr](https://github.com/rmcgirr83) in his fork, routing avatar deletion through phpBB's `avatar.manager` service instead of the legacy `avatar_delete()` function.
- Code review, bug fixes, and documentation assisted by [Claude](https://www.anthropic.com/claude).

## License

This extension is licensed under the **GNU General Public License v2.0**.

See [license.txt](license.txt) for more information.
