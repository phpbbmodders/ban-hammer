# AI extension validation — 2026-09-22

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

## Step 1: Reference documentation

Read in full this session (not from recalled memory): `coding-guidelines-33x.txt`,
`validation-policy.txt`; cross-checked all four `core.*` events this
extension subscribes to (`core.permissions`, `core.memberlist_view_profile`,
`core.session_set_custom_ban`, `core.mcp_queue_approve_details_template`)
against `events_list.rst` — all four exist, are current, and are used with
the correct `$event[...]` argument names documented there. Cache is dated
09/03/2026 (per its own `README.md`); nothing checked here appeared to need
a fresher fetch.

## Step 1a: Mechanical checks

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

## Step 2-4: Guideline and security review (this branch's 15 commits)

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

## Recommendation

**Approve.** No `[valdeny]`-level issues found in this branch's changes;
both `[valinfo]` items are judgment calls with reasoning attached, not
guideline deviations requiring denial. Formatting is otherwise clean across
every file touched (tabs, brace placement, quoting, SQL layout, comment
style all consistent with the coding guidelines and the rest of the
existing codebase).
