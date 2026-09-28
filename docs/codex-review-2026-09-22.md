# Codex code review — 2026-09-22

Full-repository review by [Codex](https://openai.com/codex/) (OpenAI), run via the
`local-codex` MCP wrapper against this repository's `master` branch
(`ff149b8`). Read-only: all 18 PHP files, YAML config/workflows, all five
templates, JavaScript, CSS, migrations, and language files. No files were
modified during the review.

Findings are ordered by severity. Line references point at the reviewed
commit and may drift as the file changes.

## 1. High — `cancel=1` bypasses confirmation and executes moderation actions

[event/banhammer_listener.php:294](../event/banhammer_listener.php#L294),
[line 343](../event/banhammer_listener.php#L343), and
[line 527](../event/banhammer_listener.php#L527).

Send a POST to `memberlist.php?mode=viewprofile&u=TARGET&bh=1` containing
`cancel=1`, without `confirm_key`. The initial guard permits it;
`confirm_box(true)` returns false, and `confirm_box(false)` also returns
false because cancellation was requested. Execution then falls through to
`user_ban()` and any requested deletions. The restriction handler has the
same flaw.

An attacker can exploit an authenticated moderator's browser when its
session accompanies the forged request (CSRF). Verified by reading phpBB
3.3.x's `confirm_box()` behavior directly — Codex reproduced both execution
paths with isolated harnesses. Mutations must execute only inside an
explicitly successful confirmation branch.

## 2. High — Group configuration bypasses founder-only group management

[controller/admin_controller.php:136](../controller/admin_controller.php#L136),
[line 154](../controller/admin_controller.php#L154), and
[event/banhammer_listener.php:562](../event/banhammer_listener.php#L562).

The ACP module requires only `a_user`. Group selection neither filters
`group_founder_manage` nor validates submitted group IDs. A non-founder
administrator with `a_user` and `m_ban` can configure a founder-managed
privileged group as the restriction group, then "restrict" another account
into it, granting that account its permissions. A crafted submission also
bypasses the dropdown's exclusion of special groups.

phpBB's own user administration controller explicitly rejects this
operation for non-founders. Validate group eligibility and founder
restrictions when saving and applying the configuration.

## 3. High — Ban permission grants unrestricted post deletion

[event/banhammer_listener.php:190](../event/banhammer_listener.php#L190),
[line 382](../event/banhammer_listener.php#L382), and
[line 723](../event/banhammer_listener.php#L723).

A moderator with `m_ban`, but without deletion permission in a particular
forum, can submit `del_posts=1` and permanently delete the target's posts
across every forum. Neither the query nor execution checks forum-specific
deletion permissions. Setting the ACP deletion default to "No" does not
prevent this.

If this broad authority is intentional, it should have an explicit
extension ACL. Otherwise, enforce the corresponding permissions before
deleting content.

## 4. Medium — Confirmed domain bans call an unloaded function

[controller/ban_domain_controller.php:96](../controller/ban_domain_controller.php#L96).

The routed controller calls `user_ban()` without loading
`includes/functions_user.php`. Standard `app.php`/`common.php` do not load
that file. On a normal installation without another extension incidentally
including it, confirming a domain ban produces an undefined-function error
and creates no ban.

Load the dependency explicitly, as the profile listener already does.

## 5. Medium — Expiry does not restore the original default group

[cron/task/restriction_expiry.php:103](../cron/task/restriction_expiry.php#L103),
[line 108](../cron/task/restriction_expiry.php#L108).

Restricting a user preserves their original group membership. At expiry,
removing the restriction group chooses a special-group default;
subsequently calling `group_user_add()` for the original group returns
`GROUP_USERS_EXIST` before changing the default. A user whose original
default was a custom group therefore keeps the wrong default, colour, or
rank. The tracking record is deleted regardless.

Restore the default using the appropriate group-attribute operation and
check errors.

## 6. Medium — Changing the configured restriction group strands existing restrictions

[cron/task/restriction_expiry.php:94](../cron/task/restriction_expiry.php#L94)
and [migrations/restrict_group.php:30](../migrations/restrict_group.php#L30).

Restrict a user into group A, then change the ACP setting to group B or "No
group" before expiry. The cron task removes B — or nothing — instead of A,
then deletes the tracking record. The user remains in A indefinitely. If
they legitimately belong to B, that membership can also be removed.

Store the applied restriction group ID in each tracking record.

## 7. Medium — Using the same group for bans and restrictions immediately undoes restrictions

[event/banhammer_listener.php:596](../event/banhammer_listener.php#L596),
[line 613](../event/banhammer_listener.php#L613).

Both ACP dropdowns permit selecting the same group. A restricted user is
deliberately not banned, so the next session ban check causes
`undo_bh_group()` to remove them from that shared group. Their restriction
record remains, and moderators see "already has an active restriction"
despite its permissions no longer applying.

Reject this configuration or make ban-group cleanup aware of active
restrictions.

## 8. Medium — Private-message deletion leaves counters and attachments inconsistent

[event/banhammer_listener.php:643](../event/banhammer_listener.php#L643).

Deleting a spammer's messages removes message and recipient rows directly,
without updating recipients' unread/new-message counts or custom-folder
counts, deleting PM notifications, or removing attachments. A recipient can
retain an unread notification pointing to a nonexistent message;
attachment files and database records remain after their parent message
disappears.

Use a deletion path that performs the bookkeeping implemented by phpBB's
PM functions.

## 9. Medium — Deleting poll votes corrupts totals and permits repeat voting

[event/banhammer_listener.php:732](../event/banhammer_listener.php#L732).

The cleanup removes the user's `POLL_VOTES_TABLE` rows, including votes in
other users' surviving topics, without decrementing poll-option totals.
After a temporary ban expires, the user can vote again because their
previous-vote record is gone, while the original vote still contributes to
the total.

Preserve these votes or update the associated totals consistently.

## 10. Medium — MCP quick bans ignore configured defaults and become permanent

[event/banhammer_listener.php:136](../event/banhammer_listener.php#L136),
[line 297](../event/banhammer_listener.php#L297).

The MCP link supplies `bh=1` without any options, bypassing the profile
form. Missing parameters default to zero: permanent duration, no email/IP
ban, no deletion, no group move, and no SFS report.

For example, an administrator's seven-day ban default becomes a permanent
username-only ban through the MCP shortcut. Open the options form or
populate the confirmation from configured defaults.

## 11. Medium — Failed SFS transfers can be reported as successful

[event/banhammer_listener.php:747](../event/banhammer_listener.php#L747).

`curl_exec()`'s return value is discarded; only the HTTP status is
examined. If the server sends HTTP 200 headers and the transfer
subsequently times out, `curl_exec()` returns false but `get_file()`
returns true. The moderator receives "All actions were performed
correctly" despite an unsuccessful transfer.

Reproduced with a simulated false cURL result and HTTP 200. Check the
transfer result and validate the response body.

## 12. Medium — Purging the extension makes temporary restrictions permanent

[migrations/restrict_group.php:46](../migrations/restrict_group.php#L46).

Purging extension data drops the restriction tracking table without
removing applied group memberships or restoring users' defaults. A user
with a one-day restriction remains restricted after purge, and the
information needed to restore them is lost.

Restore tracked users before dropping the table, or prevent purge until
outstanding restrictions are resolved.

## 13. Low — Result styling generates malformed HTML

[event/banhammer_listener.php:237](../event/banhammer_listener.php#L237) and
[memberlist_view_content_prepend.html:2](../styles/prosilver/template/event/memberlist_view_content_prepend.html#L2).

`BH_STYLE` ends with a double quote even though the template supplies its
own attribute quotes. Successful and failed action results produce
malformed attributes such as `style="background-color: green; color:
white;";"`. Remove the embedded quote. This value is fixed text, not an
XSS finding.

## 14. Low — The ACP saved-message branch is dead code

[adm/style/banhammer_body.html:5](../adm/style/banhammer_body.html#L5).

Nothing assigns `S_SAVED`; successful saves terminate through
`trigger_error()` instead. This success box can never appear through the
extension's normal flow. Remove it or implement that rendering path.

## What was checked and cleared

All PHP files passed syntax checks under PHP 8.4.25. Codex checked upstream
phpBB source directly and ran isolated, in-memory harnesses; no installed
phpBB database/browser integration environment was available for this
review.

No confirmed SQL injection or XSS was found in the reviewed input paths.
Interpolated user IDs are integer-derived, other SQL uses DBAL builders,
and phpBB's request handling escapes string inputs. ACP saves have a
form-token check. Services and event registration generally follow
extension conventions, and no executable code modifies core files. No
leftover debug output was found.

## Overall assessment

The extension has a conventional structure, but the confirmation-bypass
(#1) and permission-boundary issues (#2, #3) need fixing before deployment.
Restriction lifecycle handling and destructive cleanup also need
functional regression coverage.

## Note on verification

Claude spot-checked finding #1 by reading
[event/banhammer_listener.php:293-348](../event/banhammer_listener.php#L293-L348)
directly: the cited `confirm_box(true)` / `confirm_box(false, ...)` calls
and the fall-through to the ban code are exactly as described. The rest of
this report reflects Codex's findings as returned, not independently
re-verified line by line.
