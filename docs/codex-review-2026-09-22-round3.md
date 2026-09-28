# Codex code review — round 3 — 2026-09-22

Full-repository review by [Codex](https://openai.com/codex/) (OpenAI), run via the
`local-codex` MCP wrapper against branch `codex-review-round-2` at
`e2ad91e795b6d83d9e12efe1eb71347a1c3c7ae7` (master `fb99cd8` plus commits
`814cbbf`, `a0254d4`, `352e7b2`, `e42d7f1`, `4afa65e`, `f83744f`, `e2ad91e`).
Third review pass — the first found 14 issues (all fixed), the second found 2
regressions in those fixes plus 5 new issues (all fixed in the 6 commits this
branch adds). This pass was asked to independently re-verify all of that
history rather than assume it, and to check for anything new. Read-only; no
files were modified.

Findings are ordered by severity. Line references point at the reviewed
commit and may drift as the file changes.

**Codex's own merge verdict: "changes required" — no High-severity issue
confirmed, 6 Medium, 1 Low.**

## 1. Medium — The unique-index migration fails on existing duplicate restrictions

[migrations/restrict_unique_user.php:32](../migrations/restrict_unique_user.php#L32),
lines 35–42.

The migration drops the ordinary index and immediately creates a unique
index, without reconciling duplicate `user_id` rows. Those duplicates are
precisely the state the previous concurrent-restriction bug (fixed in
`e42d7f1`) could already have produced on a live site before this migration
ships.

**Reproduction:** Upgrade an installation containing two restriction rows for
the same user (created before the race-condition fix was in place). Unique
index creation fails, blocking the whole extension upgrade. Reproduced with
an in-memory SQLite dataset: `UNIQUE constraint failed:
banhammer_restrict.user_id`.

**Fix:** Add a prerequisite deduplication step (before the schema change,
since phpBB applies `update_schema()` before `update_data()`) that reconciles
duplicate rows, then add the unique index.

## 2. Medium — Open/Free restriction groups remain usable through existing settings or later group edits

[controller/admin_controller.php:137](../controller/admin_controller.php#L137),
[event/banhammer_listener.php:505](../event/banhammer_listener.php#L505),
[event/banhammer_listener.php:682](../event/banhammer_listener.php#L682).

The Open/Free group-type check added in `f83744f` only runs on ACP settings
submission. `safe_group_name()` (added in `a0254d4`) doesn't fetch or
validate `group_type` at all.

**Reproduction:** A site already has an Open restriction group configured
before upgrading to this fix, or an admin changes an existing Closed
restriction group to Open afterward. Restricting a user still succeeds; the
restricted user can resign via UCP, escaping the restriction while the
tracking row remains and blocks a second restriction attempt.

**Fix:** Validate group type in `safe_group_name()` (or a restriction-specific
variant) at use time too, not only at save time.

## 3. Medium — Expiry/purge can delete a pre-existing group membership that predates the restriction

[event/banhammer_listener.php:617](../event/banhammer_listener.php#L617),
[cron/task/restriction_expiry.php:104](../cron/task/restriction_expiry.php#L104),
[migrations/restrict_group_id_column.php:116](../migrations/restrict_group_id_column.php#L116).

The `GROUP_USERS_EXIST` branch added in `e42d7f1` handles the case where the
target already belongs to the restrict group, but the tracking row doesn't
record whether Ban Hammer's own action created that membership. Both
`undo_bh_group()`/expiry and purge unconditionally remove it regardless.

**Reproduction:** A user already belongs to the configured restrict group for
an unrelated, legitimate reason (e.g. a permanent administrative
assignment). A moderator applies a temporary restriction. At expiry, their
pre-existing membership is removed along with the restriction - not just the
temporary effect.

**Fix:** Record whether Ban Hammer's own action created the membership;
clean up only what it created.

## 4. Medium — A pending (not yet approved) membership is treated as a successfully applied restriction

[event/banhammer_listener.php:615-624](../event/banhammer_listener.php#L615).

`GROUP_USERS_EXIST` from `group_user_add()` also covers pending membership
requests. The fallback (`group_user_attributes('default', ...)`) doesn't
check its own result, and core only sets the default group for *approved*
members - so a pending applicant's restriction silently never takes effect,
while the tracking row still claims it did.

**Reproduction:** A user has a pending join request for a group an admin
later designates as the restrict group. Restricting them inserts the
tracking row and reports success, but their default group and effective
permissions never actually change; subsequent restriction attempts report
"already restricted."

**Fix:** Inspect membership status explicitly (pending vs. approved);
approve through the correct core operation or reject the restriction outright
if membership can't be made effective. Check the fallback call's own result.

## 5. Medium — Post deletion still wipes unrelated account data for the zero-posts and partial-deletion cases

[event/banhammer_listener.php:803](../event/banhammer_listener.php#L803),
[event/banhammer_listener.php:861-877](../event/banhammer_listener.php#L861).

The guard added in `4afa65e` only covers "there were posts, but every one
got filtered out by forum permission." It doesn't cover a target who simply
has zero posts to begin with, and it doesn't stop the account-wide cleanup
when deletion *partially* succeeds (some posts deleted in a permitted forum,
others left alone in a forum the moderator can't touch).

Confirmed via a harness directly invoking the method:

| Target's posts | Posts actually deleted | Account-wide DELETEs still run |
|---|---:|---:|
| None | none | 10 |
| All permission-blocked | none | 0 (fixed by `4afa65e`) |
| One permitted forum, one blocked | permitted post only | 10 |

**Fix:** Either remove the broad account-data cleanup from post deletion
entirely (make it a separate, explicitly authorized action), or scope it more
precisely to what was actually touched.

## 6. Medium — Ban-group cleanup still relies on current configuration, not the group actually applied to a given ban

[event/banhammer_listener.php:440](../event/banhammer_listener.php#L440),
[event/banhammer_listener.php:700](../event/banhammer_listener.php#L700),
lines 704 and 731.

This is the ban-side counterpart to finding #3 from the second review pass
(fixed for restrictions in `352e7b2`), but was explicitly out of scope for
this batch. Restated here as still-open: ban-group membership is never
recorded per user, so cleanup (`undo_bh_group`) can only compare against the
*current* `bh_group_id` setting, not whatever group a given ban actually
used.

**Reproduction:** Ban a user, moving them into group A. Change the ACP
setting to group B (or disable group-moving) before the ban expires. On
expiry, cleanup checks B, leaving the user stuck in A. Conversely, an
unrelated legitimate member of the *currently* configured group B can have
their membership removed by cleanup if they're ever seen in a non-banned
session, even though Ban Hammer never added them.

**Fix:** Give bans the same kind of per-action group tracking the restrict
feature now has. Larger design work, not a quick patch.

## 7. Low — The restriction-group dropdown still offers choices the new validator rejects

[controller/admin_controller.php:112](../controller/admin_controller.php#L112),
[controller/admin_controller.php:228](../controller/admin_controller.php#L228).

Both the move-group and restrict-group dropdowns share one generator, which
still lists Open/Free groups. Selecting one for the restrict group now
produces a generic `FORM_INVALID` error with no explanation of why.

**Fix:** Give the dropdown generator a restriction-specific filter (hide
Open/Free groups when rendering the restrict-group select), and/or a clearer
error message.

## What was checked and cleared

- **Confirmation/CSRF fallthrough** (round 1 finding #1): still fixed, both
  ban and restrict paths correctly return after an unsuccessful confirmation.
- **Founder-managed group re-validation** (round 2 finding #2): confirmed
  fixed at both save time and use time.
- **`undo_bh_group`'s restriction comparison** (round 2 finding #3): confirmed
  it now correctly uses the restriction's own recorded group, not current
  config.
- **Concurrent restriction creation**: the unique index correctly prevents a
  *new* duplicate from being created once the migration is installed (the
  installation-time gap is finding #1 above, not the runtime behavior).
- **PM/poll cleanup, MCP link defaults, SFS transport/settings-preservation
  fixes**: all confirmed still correct.
- **The new `core.permissions` listener**: confirmed correctly integrated -
  preserves existing permission definitions, supplies the right lang/category
  keys, and the migration dependency chain (table → column → permission →
  unique index) has no cycle.
- No SQL injection, XSS, or other injection issue found in reviewed request
  handling, SQL construction, domain validation, the result-message
  allowlist, templates, or JavaScript.
- All 22 PHP files pass `php -l` under PHP 8.4.25; JavaScript passes `node
  --check`; YAML/`composer.json` parse; `git diff --check` passes.

## Note on verification

This was Codex's own review output, using in-memory/harness reproductions
(SQLite, isolated PHP snippets) rather than a live phpBB install or the
project's actual CI (which has database and functional jobs disabled on this
branch, since those flags only exist on the separate, still-open
`epv-and-test-coverage` branch). Findings were **not** independently
re-verified line-by-line by Claude before this file was written; that
verification pass happens next, in the conversation, before any fix is
applied.
