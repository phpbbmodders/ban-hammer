# TODO

## Planned Features

Agreed 10/20/2026, built one pull request at a time. Every new feature is off by default and works on phpBB 3.3.19+ and 4.0.

1. ~~**phpBB 4.0 support**~~ - done in #50.
2. **Ban list** as an ACP setting, moved in from [phpbbmodders/banlist](https://github.com/phpbbmodders/banlist); that repo is archived afterwards.
3. **Automatic ban after X warnings**: number of warnings, permanent or temporary length, and ban reason set in the ACP. Logged to the admin and moderator logs and the user's notes, like any other ban.
4. **Ban export/import** as CSV: email, username and IP bans with reason and end date. Import shows a preview first and skips bans the board already has. Requested in [Export/Import banned email list](https://www.phpbb.com/community/viewtopic.php?t=2669812).

Kept separate: [phpbbmodders/groupwarn](https://github.com/phpbbmodders/groupwarn).

## Validation Considerations

The [archived September 22 validation](TODO/archive/archive1.md#ai-validation-review-2026-09-22) recorded two informational caveats, not denial findings. Archiving does not mark these resolved:

- Per-row group-membership operations may warrant batching if a measured workload requires it; no immediate optimization was required by the report.
- The branch changed an existing migration's purge/revert path. Preserve that compatibility history and assess migration policy before any future release; do not rewrite applied migrations as archive cleanup.

## Archived Reviews

The September 22 Codex reports were superseded by the fixes merged in PRs #36 and #38. Their original findings remain available as historical evidence:

- [Initial review](TODO/archive/archive1.md#codex-review-2026-09-22)
- [Round 3](TODO/archive/archive1.md#codex-review-2026-09-22-round3)
- [Round 4](TODO/archive/archive1.md#codex-review-2026-09-22-round4)
- [AI extension validation](TODO/archive/archive1.md#ai-validation-review-2026-09-22) - historical approval of the reviewed branch, not current-tree certification.
