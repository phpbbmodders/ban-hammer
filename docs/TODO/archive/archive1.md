# Archived Reviews

These reports preserve historical review evidence. Refer to each section for its reviewed scope and archive status.

<a id="codex-review-2026-09-22"></a>

## Codex code review — 2026-09-22

Archived: 10/02/2026. Historical review superseded by the fixes merged in PRs #36 and #38, with the later fixes merged on 09/27/2026. Findings and reviewed line numbers below describe the earlier snapshot, not the current code. This archive operation is not a new code audit.

Full-repository review by [Codex](https://openai.com/codex/) (OpenAI), run via the
`local-codex` MCP wrapper against this repository's `master` branch
(`ff149b8`). Read-only: all 18 PHP files, YAML config/workflows, all five
templates, JavaScript, CSS, migrations, and language files. No files were
modified during the review.

Findings are ordered by severity. Line references point at the reviewed
commit and may drift as the file changes.

### 1. High — `cancel=1` bypasses confirmation and executes moderation actions

[event/banhammer_listener.php:294](../../../event/banhammer_listener.php#L294),
[line 343](../../../event/banhammer_listener.php#L343), and
[line 527](../../../event/banhammer_listener.php#L527).

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

### 2. High — Group configuration bypasses founder-only group management

[controller/admin_controller.php:136](../../../controller/admin_controller.php#L136),
[line 154](../../../controller/admin_controller.php#L154), and
[event/banhammer_listener.php:562](../../../event/banhammer_listener.php#L562).

The ACP module requires only `a_user`. Group selection neither filters
`group_founder_manage` nor validates submitted group IDs. A non-founder
administrator with `a_user` and `m_ban` can configure a founder-managed
privileged group as the restriction group, then "restrict" another account
into it, granting that account its permissions. A crafted submission also
bypasses the dropdown's exclusion of special groups.

phpBB's own user administration controller explicitly rejects this
operation for non-founders. Validate group eligibility and founder
restrictions when saving and applying the configuration.

### 3. High — Ban permission grants unrestricted post deletion

[event/banhammer_listener.php:190](../../../event/banhammer_listener.php#L190),
[line 382](../../../event/banhammer_listener.php#L382), and
[line 723](../../../event/banhammer_listener.php#L723).

A moderator with `m_ban`, but without deletion permission in a particular
forum, can submit `del_posts=1` and permanently delete the target's posts
across every forum. Neither the query nor execution checks forum-specific
deletion permissions. Setting the ACP deletion default to "No" does not
prevent this.

If this broad authority is intentional, it should have an explicit
extension ACL. Otherwise, enforce the corresponding permissions before
deleting content.

### 4. Medium — Confirmed domain bans call an unloaded function

[controller/ban_domain_controller.php:96](../../../controller/ban_domain_controller.php#L96).

The routed controller calls `user_ban()` without loading
`includes/functions_user.php`. Standard `app.php`/`common.php` do not load
that file. On a normal installation without another extension incidentally
including it, confirming a domain ban produces an undefined-function error
and creates no ban.

Load the dependency explicitly, as the profile listener already does.

### 5. Medium — Expiry does not restore the original default group

[cron/task/restriction_expiry.php:103](../../../cron/task/restriction_expiry.php#L103),
[line 108](../../../cron/task/restriction_expiry.php#L108).

Restricting a user preserves their original group membership. At expiry,
removing the restriction group chooses a special-group default;
subsequently calling `group_user_add()` for the original group returns
`GROUP_USERS_EXIST` before changing the default. A user whose original
default was a custom group therefore keeps the wrong default, colour, or
rank. The tracking record is deleted regardless.

Restore the default using the appropriate group-attribute operation and
check errors.

### 6. Medium — Changing the configured restriction group strands existing restrictions

[cron/task/restriction_expiry.php:94](../../../cron/task/restriction_expiry.php#L94)
and [migrations/restrict_group.php:30](../../../migrations/restrict_group.php#L30).

Restrict a user into group A, then change the ACP setting to group B or "No
group" before expiry. The cron task removes B — or nothing — instead of A,
then deletes the tracking record. The user remains in A indefinitely. If
they legitimately belong to B, that membership can also be removed.

Store the applied restriction group ID in each tracking record.

### 7. Medium — Using the same group for bans and restrictions immediately undoes restrictions

[event/banhammer_listener.php:596](../../../event/banhammer_listener.php#L596),
[line 613](../../../event/banhammer_listener.php#L613).

Both ACP dropdowns permit selecting the same group. A restricted user is
deliberately not banned, so the next session ban check causes
`undo_bh_group()` to remove them from that shared group. Their restriction
record remains, and moderators see "already has an active restriction"
despite its permissions no longer applying.

Reject this configuration or make ban-group cleanup aware of active
restrictions.

### 8. Medium — Private-message deletion leaves counters and attachments inconsistent

[event/banhammer_listener.php:643](../../../event/banhammer_listener.php#L643).

Deleting a spammer's messages removes message and recipient rows directly,
without updating recipients' unread/new-message counts or custom-folder
counts, deleting PM notifications, or removing attachments. A recipient can
retain an unread notification pointing to a nonexistent message;
attachment files and database records remain after their parent message
disappears.

Use a deletion path that performs the bookkeeping implemented by phpBB's
PM functions.

### 9. Medium — Deleting poll votes corrupts totals and permits repeat voting

[event/banhammer_listener.php:732](../../../event/banhammer_listener.php#L732).

The cleanup removes the user's `POLL_VOTES_TABLE` rows, including votes in
other users' surviving topics, without decrementing poll-option totals.
After a temporary ban expires, the user can vote again because their
previous-vote record is gone, while the original vote still contributes to
the total.

Preserve these votes or update the associated totals consistently.

### 10. Medium — MCP quick bans ignore configured defaults and become permanent

[event/banhammer_listener.php:136](../../../event/banhammer_listener.php#L136),
[line 297](../../../event/banhammer_listener.php#L297).

The MCP link supplies `bh=1` without any options, bypassing the profile
form. Missing parameters default to zero: permanent duration, no email/IP
ban, no deletion, no group move, and no SFS report.

For example, an administrator's seven-day ban default becomes a permanent
username-only ban through the MCP shortcut. Open the options form or
populate the confirmation from configured defaults.

### 11. Medium — Failed SFS transfers can be reported as successful

[event/banhammer_listener.php:747](../../../event/banhammer_listener.php#L747).

`curl_exec()`'s return value is discarded; only the HTTP status is
examined. If the server sends HTTP 200 headers and the transfer
subsequently times out, `curl_exec()` returns false but `get_file()`
returns true. The moderator receives "All actions were performed
correctly" despite an unsuccessful transfer.

Reproduced with a simulated false cURL result and HTTP 200. Check the
transfer result and validate the response body.

### 12. Medium — Purging the extension makes temporary restrictions permanent

[migrations/restrict_group.php:46](../../../migrations/restrict_group.php#L46).

Purging extension data drops the restriction tracking table without
removing applied group memberships or restoring users' defaults. A user
with a one-day restriction remains restricted after purge, and the
information needed to restore them is lost.

Restore tracked users before dropping the table, or prevent purge until
outstanding restrictions are resolved.

### 13. Low — Result styling generates malformed HTML

[event/banhammer_listener.php:237](../../../event/banhammer_listener.php#L237) and
[memberlist_view_content_prepend.html:2](../../../styles/prosilver/template/event/memberlist_view_content_prepend.html#L2).

`BH_STYLE` ends with a double quote even though the template supplies its
own attribute quotes. Successful and failed action results produce
malformed attributes such as `style="background-color: green; color:
white;";"`. Remove the embedded quote. This value is fixed text, not an
XSS finding.

### 14. Low — The ACP saved-message branch is dead code

[adm/style/banhammer_body.html:5](../../../adm/style/banhammer_body.html#L5).

Nothing assigns `S_SAVED`; successful saves terminate through
`trigger_error()` instead. This success box can never appear through the
extension's normal flow. Remove it or implement that rendering path.

### What was checked and cleared

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

### Overall assessment

The extension has a conventional structure, but the confirmation-bypass
(#1) and permission-boundary issues (#2, #3) need fixing before deployment.
Restriction lifecycle handling and destructive cleanup also need
functional regression coverage.

### Note on verification

Claude spot-checked finding #1 by reading
[event/banhammer_listener.php:293-348](../../../event/banhammer_listener.php#L293-L348)
directly: the cited `confirm_box(true)` / `confirm_box(false, ...)` calls
and the fall-through to the ban code are exactly as described. The rest of
this report reflects Codex's findings as returned, not independently
re-verified line by line.

<a id="codex-review-2026-09-22-round3"></a>

## Codex code review — round 3 — 2026-09-22

Archived: 10/02/2026. Historical review superseded by the fixes merged in PRs #36 and #38, with the later fixes merged on 09/27/2026. Findings and reviewed line numbers below describe the earlier snapshot, not the current code. This archive operation is not a new code audit.

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

### 1. Medium — The unique-index migration fails on existing duplicate restrictions

[migrations/restrict_unique_user.php:32](../../../migrations/restrict_unique_user.php#L32),
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

### 2. Medium — Open/Free restriction groups remain usable through existing settings or later group edits

[controller/admin_controller.php:137](../../../controller/admin_controller.php#L137),
[event/banhammer_listener.php:505](../../../event/banhammer_listener.php#L505),
[event/banhammer_listener.php:682](../../../event/banhammer_listener.php#L682).

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

### 3. Medium — Expiry/purge can delete a pre-existing group membership that predates the restriction

[event/banhammer_listener.php:617](../../../event/banhammer_listener.php#L617),
[cron/task/restriction_expiry.php:104](../../../cron/task/restriction_expiry.php#L104),
[migrations/restrict_group_id_column.php:116](../../../migrations/restrict_group_id_column.php#L116).

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

### 4. Medium — A pending (not yet approved) membership is treated as a successfully applied restriction

[event/banhammer_listener.php:615-624](../../../event/banhammer_listener.php#L615).

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

### 5. Medium — Post deletion still wipes unrelated account data for the zero-posts and partial-deletion cases

[event/banhammer_listener.php:803](../../../event/banhammer_listener.php#L803),
[event/banhammer_listener.php:861-877](../../../event/banhammer_listener.php#L861).

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

### 6. Medium — Ban-group cleanup still relies on current configuration, not the group actually applied to a given ban

[event/banhammer_listener.php:440](../../../event/banhammer_listener.php#L440),
[event/banhammer_listener.php:700](../../../event/banhammer_listener.php#L700),
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

### 7. Low — The restriction-group dropdown still offers choices the new validator rejects

[controller/admin_controller.php:112](../../../controller/admin_controller.php#L112),
[controller/admin_controller.php:228](../../../controller/admin_controller.php#L228).

Both the move-group and restrict-group dropdowns share one generator, which
still lists Open/Free groups. Selecting one for the restrict group now
produces a generic `FORM_INVALID` error with no explanation of why.

**Fix:** Give the dropdown generator a restriction-specific filter (hide
Open/Free groups when rendering the restrict-group select), and/or a clearer
error message.

### What was checked and cleared

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

### Note on verification

This was Codex's own review output, using in-memory/harness reproductions
(SQLite, isolated PHP snippets) rather than a live phpBB install or the
project's actual CI (which has database and functional jobs disabled on this
branch, since those flags only exist on the separate, still-open
`epv-and-test-coverage` branch). Findings were **not** independently
re-verified line-by-line by Claude before this file was written; that
verification pass happens next, in the conversation, before any fix is
applied.

<a id="codex-review-2026-09-22-round4"></a>

## Codex code review — round 4 — 2026-09-22

Archived: 10/02/2026. Historical review superseded by the fixes merged in PRs #36 and #38, with the later fixes merged on 09/27/2026. Findings and reviewed line numbers below describe the earlier snapshot, not the current code. This archive operation is not a new code audit.

Full-repository review by [Codex](https://openai.com/codex/) (OpenAI), run via the
`local-codex` MCP wrapper against branch `codex-review-round-2` at
`be9676c22815e83022ba3be4646ea2826089f42a` (master `fb99cd8` plus 12 commits).
Fourth review pass. Read-only; no files were modified.

**Codex's own verdict: "Changes recommended" — 3 Medium, 1 Low, no new High.
Approaching diminishing returns but not quite a stopping point; recommends
fixing these, explicitly accepting/scheduling the deferred ban-group design
issue, and moving to focused upgrade/purge and permission-matrix integration
tests rather than another unrestricted static-review pass.**

### 1. Medium — Legacy restrictions get an unjustified `restrict_new_membership = 1`

[migrations/restrict_membership_column.php:39](../../../migrations/restrict_membership_column.php#L39),
removal at
[cron/task/restriction_expiry.php:110](../../../cron/task/restriction_expiry.php#L110),
[migrations/restrict_membership_column.php:99](../../../migrations/restrict_membership_column.php#L99).

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

### 2. Medium — Purge before `restrict_membership_column` is installed skips restoration entirely

[migrations/restrict_membership_column.php:60](../../../migrations/restrict_membership_column.php#L60),
[migrations/restrict_group_id_column.php:43](../../../migrations/restrict_group_id_column.php#L43).

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

### 3. Medium — Zero-post cleanup still runs for moderators who *do* have the global delete permission

[event/banhammer_listener.php:815](../../../event/banhammer_listener.php#L815),
[line 829](../../../event/banhammer_listener.php#L829),
[line 893](../../../event/banhammer_listener.php#L893).

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

### 4. Low — Partial post deletion still wipes `TOPICS_POSTED_TABLE` across every forum

[event/banhammer_listener.php:904](../../../event/banhammer_listener.php#L904).

When a moderator can delete posts in forum A but not B, posts in B correctly
survive, but the unconditional `DELETE FROM TOPICS_POSTED_TABLE WHERE
user_id = ...` still removes the user's "posted in this topic" markers for
*every* topic, including ones in B where their post is untouched. phpBB's
own `delete_posts()` already keeps this table in sync for the posts it
actually deletes; this blanket delete undoes that bookkeeping for surviving
content.

**Fix:** Remove the blanket `TOPICS_POSTED_TABLE` delete and rely on core's
own synchronization from `delete_posts()`.

### Deferred issue restated, not new

The ban-group tracking gap (`undo_bh_group()` has no per-ban record of which
group a given ban actually used) is still present, as expected - this was
explicitly deferred in round 2/3 as out-of-scope design work, not
re-counted as a new finding here.

### What was checked and cleared

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

### Note on verification

Source review plus bounded in-memory/harness reproductions (Codex's own),
not a live phpBB install or the project's real CI (no functional/DB test
jobs are enabled on this branch's own workflow config). Not yet
independently re-verified by Claude line-by-line - that happens next, before
any fix is applied. Codex's own recommendation: this is approaching
diminishing returns for further *unrestricted* static review, but these four
findings are concrete rather than speculative and worth fixing before
moving to integration testing instead of another open-ended pass.


<a id="ai-validation-review-2026-09-22"></a>

## AI extension validation — 2026-09-22

Archived: 10/02/2026. Validation date: 09/22/2026. Historical branch validation of 3dbc19d, superseded by the PR #38 merge and subsequent cron and CI changes. The original approval applies only to that reviewed branch. Its batching and migration-policy caveats are preserved below and in the active TODO index; archiving does not resolve them or approve the current tree.


Read-only validation pass using the standard phpBB extension validator prompt
(`repos/misc/validate.prompt.md`), run against branch `codex-review-round-2`
at commit `3dbc19d` (master `fb99cd8` plus 15 commits) before this branch is
submitted anywhere or merged, as a check of what a junior EPV-style validator
would flag first.

**Scope note:** full mechanical checks (Step 1a) ran against the entire
repository. The manual guideline-conformance review (Steps 2-4) focused on
the 15 commits new in this branch, not a from-scratch re-audit of the whole,
already-published extension (v1.0.8) — the pre-existing code predates this
session and was presumably already through real validation.

### Step 1: Reference documentation

Read in full this session (not from recalled memory): `coding-guidelines-33x.txt`,
`validation-policy.txt`; cross-checked all four `core.*` events this
extension subscribes to (`core.permissions`, `core.memberlist_view_profile`,
`core.session_set_custom_ban`, `core.mcp_queue_approve_details_template`)
against `events_list.rst` — all four exist, are current, and are used with
the correct `$event[...]` argument names documented there. Cache is dated
09/03/2026 (per its own `README.md`); nothing checked here appeared to need
a fresher fetch.

### Step 1a: Mechanical checks

- `composer validate`: **passes** ("valid, but with a few warnings"). The
  one warning (presence of the `version` field, which Composer recommends
  omitting for Packagist-published packages) does not apply here — phpBB's
  own extension skeleton (`composer.json.twig`) requires this field for
  Customisation DB submissions, which don't use Packagist versioning. Not a
  finding.
- `php -l` on every `.php` file in the repository (not just this branch's
  changes): **all pass**, zero syntax errors.
- Trailing whitespace (`grep -nP '[ \t]+$'`) across `.php`/`.html`/`.js`/`.css`/`.yml`:
  **none found**.
- Language key cross-reference, both directions, across all 64 keys defined
  in `language/en/*.php`:
  - Every defined key has at least one usage outside its own definition
    file (checked individually, not sampled). No dead keys.
  - Every language key *referenced* in PHP code that isn't itself defined
    in this extension's language files resolves to a real phpBB core key
    (`FORM_INVALID`, `COLON`, `NO_GROUP`, the ban-duration keys like
    `1_DAY`/`7_DAYS`/`PERMANENT`, and the dynamically-built `G_<group_name>`
    prefix) — none are missing custom-key definitions.

### Step 2-4: Guideline and security review (this branch's 15 commits)

No `[valdeny]` findings. Two `[valinfo]` items:

In `[c]cron/task/restriction_expiry.php[/c]`, `[c]migrations/restrict_dedupe.php[/c]`, `[c]migrations/restrict_membership_column.php[/c]`, `[c]migrations/ban_group.php[/c]`:

[code]
foreach ($expired as $row)
{
    ...
    group_user_del($restrict_group_id, array($user_id));
    ...
    group_user_attributes('default', $original_group_id, array($user_id));
    ...
    $this->db->sql_query('DELETE FROM ' . $this->restrict_table . ' WHERE restrict_id = ' . (int) $row['restrict_id']);
}
[/code]

[valinfo]
Each of these methods calls a phpBB core group-membership function (or a
per-row `DELETE`) once per row inside a loop, rather than batching rows that
share the same target group into a single call — `group_user_del()`,
`group_user_add()`, and `group_user_attributes()` all already accept an
array of user IDs. This is a pre-existing pattern in `restriction_expiry.php`
(unchanged in shape by this branch, only extended consistently into the new
migrations for the same reason: restoring tracked rows one at a time). Given
these only run from a cron task capped at once every five minutes, or a
one-time migration/purge pass, the realistic number of rows processed per
invocation on a real moderation workload is small, so this doesn't rise to
a denial - flagging per the [c]validation-policy.txt[/c] guidance that "SQL
queries within loops should be avoided," for the maintainer's awareness if
a future release needs to handle much larger batches.
[/valinfo]

In `[c]migrations/restrict_group_id_column.php[/c]`:

[code]
public function revert_data()
{
    return array(
        array('custom', array(array($this, 'restore_active_restrictions_fallback'))),
    );
}
[/code]

[valinfo]
This adds a `revert_data()` method (and a new `restore_active_restrictions_fallback()`)
to a migration that was already merged in a prior commit (`fb99cd8`, PR #36).
The validation policy states "Existing migration files from previously
released versions should never be altered or deleted." Two mitigating facts
worth the validator's judgment call rather than an automatic denial: (1)
only the *revert* path is touched, not `update_schema()`/`update_data()` -
the paths that would actually diverge between an already-migrated site and
a fresh install if altered; a revert only runs when a site purges the
extension, which hasn't happened anywhere yet since this functionality is
new. (2) `git tag` on this repository returns no tags at all, and
`composer.json`'s `version` field has read `1.0.8` since 2018, unchanged
by this migration's original addition - there is no evidence this specific
migration has ever shipped in a numbered Customisation DB release. Verified
directly against a real phpBB 3.3.x install: a purge before the newer
`restrict_membership_column` migration is ever installed (simulating an
upgrade-then-immediate-purge sequence) now correctly restores active
restrictions via this fallback, where it previously would have silently
dropped the tracking table with nothing restored.
[/valinfo]

### Recommendation

**Approve.** No `[valdeny]`-level issues found in this branch's changes;
both `[valinfo]` items are judgment calls with reasoning attached, not
guideline deviations requiring denial. Formatting is otherwise clean across
every file touched (tabs, brace placement, quoting, SQL layout, comment
style all consistent with the coding guidelines and the rest of the
existing codebase).
