# TODO

## Planned Features

Agreed 10/20/2026, built one pull request at a time. Every new feature is off by default and works on phpBB 3.3.19+ and 4.0.

1. ~~**phpBB 4.0 support**~~ - done in #50.
2. ~~**Ban list**~~ - done in #51; phpbbmodders/banlist is archived.
3. **Automatic ban after X warnings** - in #52, on hold until phpBB merges [phpbb/phpbb#7101](https://github.com/phpbb/phpbb/pull/7101): phpBB master's MCP warn page shows a PHP warning that fails #52's 4.0 tests.
4. **Ban export/import** as CSV: email, username and IP bans with reason and end date. Import shows a preview first and skips bans the board already has. Full plan: [todo-ban-export-import.md](TODO/todo-ban-export-import.md).

Kept separate: [phpbbmodders/groupwarn](https://github.com/phpbbmodders/groupwarn).

## phpBB Core Bugs Found While Testing

Bugs in phpBB itself, found on the seed-forum test boards. Each needs a tracker ticket and a phpBB pull request; nothing to change in Ban Hammer.

- **Unban log entries break the ACP log on phpBB 4.0** (found 10/20/2026, not reported yet). Every unban on 4.0, including the ACP's own unban page, writes admin and moderator log entries whose data holds an array, so showing the log (for example the ACP front page) gives `PHP Warning: Array to string conversion` in `phpbb/language/language.php`. Cause, in `\phpbb\ban\manager::unban()` on master:
  - It logs `[$unbanned_users]`, an array, where `ban()` logs `implode(', ', $items)`. For email and IP bans that array is always empty, so the log doesn't even name what was unbanned. Fix: log the unbanned items joined into a string, the way `ban()` does.
  - For username bans `after_unban()` returns user id => username, but the user-notes loop does `foreach ($unbanned_users as $user_id)`, so it uses the usernames as user ids and the "unbanned" note never reaches the right user. Fix: loop over the keys.
  - phpBB 3.3's `user_unban()` logs a joined string, so this only affects 4.0. Tracker: affects version 4.0.0-b1, component Ban System or Events; fix goes in a `master` PR with a test.
- **Undefined `$avatar_img` in three MCP template events on master**: reported as [PHPBB-17752](https://tracker.phpbb.com/browse/PHPBB-17752), fix in [phpbb/phpbb#7101](https://github.com/phpbb/phpbb/pull/7101). Blocks #52.

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
