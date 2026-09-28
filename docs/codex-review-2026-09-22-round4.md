# Codex code review — round 4 — 2026-09-22

Full-repository review by [Codex](https://openai.com/codex/) (OpenAI), run via the
`local-codex` MCP wrapper against branch `codex-review-round-2` at
`be9676c22815e83022ba3be4646ea2826089f42a` (master `fb99cd8` plus 12 commits).
Fourth review pass. Read-only; no files were modified.

**Codex's own verdict: "Changes recommended" — 3 Medium, 1 Low, no new High.
Approaching diminishing returns but not quite a stopping point; recommends
fixing these, explicitly accepting/scheduling the deferred ban-group design
issue, and moving to focused upgrade/purge and permission-matrix integration
tests rather than another unrestricted static-review pass.**

## 1. Medium — Legacy restrictions get an unjustified `restrict_new_membership = 1`

[migrations/restrict_membership_column.php:39](../migrations/restrict_membership_column.php#L39),
removal at
[cron/task/restriction_expiry.php:110](../cron/task/restriction_expiry.php#L110),
[migrations/restrict_membership_column.php:99](../migrations/restrict_membership_column.php#L99).

The migration's comment claims existing rows are "accurately" defaulted to
`1` because pre-fix code always resulted in a fresh membership. That's
wrong: before round 3's `GROUP_USERS_EXIST` handling existed,
`do_restrict_stuff()` inserted the tracking row and **ignored**
`group_user_add()`'s return value entirely - so a user who already belonged
to the restrict group before being "restricted" would still get a tracking
row, with no way to tell, after the fact, whether membership was created or
pre-existing.

**Reproduction:** On `fb99cd8` (before round 3), restrict a user already
belonging to the configured restrict group. Upgrade to this branch. Their
row gets `restrict_new_membership = 1` by the backfill. At expiry or purge,
their pre-existing membership gets removed - the exact bug round 3's column
was meant to close, now reintroduced for every restriction created before
the column existed.

**Fix:** Don't claim certainty for legacy rows. Either leave them
unreconciled with an explicit note that upgrade-time correctness for
pre-existing rows is a known limitation, or attempt reconciliation (e.g.
compare `original_group_id` against `restrict_group_id`, or query current
membership timing if available) rather than blanket-defaulting to 1.

## 2. Medium — Purge before `restrict_membership_column` is installed skips restoration entirely

[migrations/restrict_membership_column.php:60](../migrations/restrict_membership_column.php#L60),
[migrations/restrict_group_id_column.php:43](../migrations/restrict_group_id_column.php#L43).

Round 3 moved `restore_active_restrictions()` out of
`restrict_group_id_column.php` (already on `master` via #36) into the new
`restrict_membership_column.php`, reasoning that the older migration's
revert runs *after* the newer one and would find the column already
dropped.

**Reproduction:** A site is running `fb99cd8` (has `restrict_group_id_column`
installed, does NOT have `restrict_membership_column` - it doesn't exist
yet). The admin disables the extension and purges it (deletes data) via the
normal "Disable → Delete data" ACP flow, **without re-enabling it first**
to pick up new migrations. phpBB's purge only reverts migrations that are
actually installed; it doesn't discover and install new ones first to then
revert them. `restrict_membership_column`'s `revert_data()` therefore never
runs, and `restrict_group_id_column.php` (the migration that IS installed)
no longer has any restoration logic at all, since round 3 removed it. The
tracking table gets dropped with active restrictions never restored - worse
than round 2's original purge behavior before either migration existed.

**Fix:** Keep a restoration fallback in the older, already-installed
migration too (idempotent - if `restrict_membership_column` already handled
it and cleared the table, the older one's fallback finds nothing to do and
is a harmless no-op).

## 3. Medium — Zero-post cleanup still runs for moderators who *do* have the global delete permission

[event/banhammer_listener.php:815](../event/banhammer_listener.php#L815),
[line 829](../event/banhammer_listener.php#L829),
[line 893](../event/banhammer_listener.php#L893).

Round 3's fix for "del_posts wipes account data even with zero posts" is
nested inside the `if (!$this->auth->acl_get('m_banhammer_del_posts_all'))`
branch. A moderator who *does* have that permission never reaches the
empty-`$posts` check at all - the account-wide cleanup (bookmarks, drafts,
notifications, etc.) still runs unconditionally even when the target has no
posts and the moderator's post-deletion authority had literally nothing to
act on.

**Reproduction:** Confirmed via harness: a target with zero posts, deleted
by a moderator with `m_banhammer_del_posts_all`, still triggers 10
account-wide DELETE statements despite `delete_posts()` doing nothing.

**Fix:** Move the empty-`$posts` check outside the permission branch, after
filtering, so it applies regardless of which permission path granted access.

## 4. Low — Partial post deletion still wipes `TOPICS_POSTED_TABLE` across every forum

[event/banhammer_listener.php:904](../event/banhammer_listener.php#L904).

When a moderator can delete posts in forum A but not B, posts in B correctly
survive, but the unconditional `DELETE FROM TOPICS_POSTED_TABLE WHERE
user_id = ...` still removes the user's "posted in this topic" markers for
*every* topic, including ones in B where their post is untouched. phpBB's
own `delete_posts()` already keeps this table in sync for the posts it
actually deletes; this blanket delete undoes that bookkeeping for surviving
content.

**Fix:** Remove the blanket `TOPICS_POSTED_TABLE` delete and rely on core's
own synchronization from `delete_posts()`.

## Deferred issue restated, not new

The ban-group tracking gap (`undo_bh_group()` has no per-ban record of which
group a given ban actually used) is still present, as expected - this was
explicitly deferred in round 2/3 as out-of-scope design work, not
re-counted as a new finding here.

## What was checked and cleared

- Migration ordering on a **fully upgraded** installation: dedup → unique
  index → membership column is correctly sequenced; purge-time data revert
  correctly runs before schema revert. (The gap is specifically the
  *partially* upgraded case, finding #2 above.)
- New restriction requests: ownership recorded correctly going forward;
  pending membership correctly fails without a stray tracking row; the
  unique index correctly prevents a concurrent duplicate insert.
- Founder-management and Open/Free group validation: present and consistent
  at both save time and use time, and the dropdown now agrees with the
  validator.
- All previously-fixed items from rounds 1-3 re-confirmed still correct:
  confirm-box bypass, permission checks on privileged actions, form-token
  check, `bh_res` allowlist, domain validation, MCP link defaults, domain-ban
  include, restriction restoration using the recorded group, SFS
  settings/transport handling, poll-vote preservation, PM cleanup delegating
  to core.
- All 24 PHP files pass `php -l`; migrations, YAML, templates, JS, CSS,
  language files, and package metadata reviewed with no new injection,
  script-execution, or secret-exposure issue found.

## Note on verification

Source review plus bounded in-memory/harness reproductions (Codex's own),
not a live phpBB install or the project's real CI (no functional/DB test
jobs are enabled on this branch's own workflow config). Not yet
independently re-verified by Claude line-by-line - that happens next, before
any fix is applied. Codex's own recommendation: this is approaching
diminishing returns for further *unrestricted* static review, but these four
findings are concrete rather than speculative and worth fixing before
moving to integration testing instead of another open-ended pass.
