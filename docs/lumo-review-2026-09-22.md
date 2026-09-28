# Lumo code review — 2026-09-22

Full-repository review by [Proton Lumo](https://lumo.proton.me/) (Proton
AG), run via `lumo-tamer`'s CLI against a single bundled text dump of this
repository's source (all `git ls-files` content, concatenated with
`=== FILE: path ===` markers — Lumo has no filesystem/tool access of its
own in this setup, so it worked from that one paste rather than reading the
repository directly).

**Read this alongside the same day's [Codex review](codex-review-2026-09-22.md).**
Lumo's output was considerably less reliable: its headline "High"
severity finding is a confirmed false positive (see below), and several
other findings visibly reverse themselves mid-answer ("Correction:",
"Re-evaluation:", "The Actual Bug:" repeated three times for the same
item) before landing on a final claim. Findings are reproduced below for
the record, with verification notes.

## Confirmed false positive: "High — Logic Error: Success/Fail Inversion"

Lumo's top claim: `event/banhammer_listener.php`'s group-move check —

```php
$return = group_user_add($this->config['bh_group_id'], array($this->user_id), array($this->data['username']), $group_name, true);

if ($return != false)
{
	$error[] = 'ERROR_MOVE_GROUP';
}
```

— supposedly treats success as failure, because Lumo assumed
`group_user_add()` returns `true`/truthy on success.

**This is wrong.** Claude checked phpBB 3.3.x's actual
[`group_user_add()`](https://github.com/phpbb/phpbb/blob/3.3.x/phpBB/includes/functions_user.php#L2716)
source directly: the success path ends with the comment `// Return false -
no error` followed by `return false;`. Every early-exit/error path (e.g.
`'NO_USER'`, `'GROUP_USERS_INVALID'`, `'GROUP_USERS_EXIST'`) returns a
non-empty string instead. `group_user_del()`'s docblock in the same file
states the convention explicitly: "@return false if no errors occurred,
else the user lang string for the relevant error." So `$return != false`
is true only when an actual error string came back — the code's logic is
**correct**, not inverted. No fix needed here.

## Other findings, as returned

Severity/labels are Lumo's own; not independently re-verified beyond the
spot checks noted.

- **Medium — Direct SQL interpolation in `bh_del_privmsgs()`**
  (`event/banhammer_listener.php`, `WHERE author_id = $user_id`, no
  explicit `(int)` cast at the point of use). Claude checked: `$user_id`
  here is `$this->user_id`, a class property set internally, not read
  directly from the request at this point — so this isn't an exploitable
  path today, but Lumo's suggestion to cast explicitly at the query site
  (defense in depth, matches normal phpBB style) is fair. Consistent with
  Codex's same-day review, which also found no confirmed SQL injection.
- **Low — `ban_domain_controller.php` relies on `confirm_box` rather than
  explicit `add_form_key`/`check_form_key`.** Not independently checked;
  worth comparing against Codex's finding #1 (a real confirmed CSRF/
  confirmation-bypass in the *listener's* confirm_box handling, different
  code path) before acting on this one.
- **Low — SFS `get_file()` doesn't validate response content, only HTTP
  status.** Same underlying issue as Codex's finding #11, described less
  precisely (Codex identified the exact bug: `curl_exec()`'s return value
  is discarded).
- **Low — `cron/task/restriction_expiry.php` has no transactional
  safety; a partial failure mid-loop can delete the tracking row before
  the group is restored.** Same underlying issue as Codex's finding #5/#6,
  described less precisely.
- **Low — dead "manage" mode reference in `acp/banhammer_module.php`.**
  Not checked.
- **Medium — `trigger_error(E_USER_WARNING)` used for user-facing errors
  instead of phpBB's redirect/exception conventions.** Plausible style
  critique; not independently checked.

## Overall assessment (Lumo's own words)

> The `ban-hammer` extension is functional and follows most phpBB 3.3.x
> architectural conventions (services, events, migrations). However, it
> contains a critical logic bug in the group moving functionality that
> causes successful operations to be flagged as failures, confusing
> administrators. [...] Addressing the logic inversion and standardizing
> error handling should be the top priority.

**Claude's assessment of this review:** the recommended top priority is
based on a misread of phpBB's own API contract and should not be acted
on. Treat this review as a rougher, less trustworthy second opinion than
the same-day Codex review — useful for the overlapping findings (SFS
error handling, cron transactional safety) as corroboration, but verify
independently before acting on anything unique to this report.

## Setup notes

`lumo-tamer`'s CLI has no working `-u`/`-q`/`--upload` flags despite the
project README documenting them: its one-shot mode is
`if (query && !query.startsWith('-')) { singleQuery(...) }`
([src/cli/client.ts:41](../../lumo-tamer/src/cli/client.ts#L41)) — any
argument starting with `-` falls through to interactive mode instead,
which then exits immediately on closed stdin. Worked around by passing
the full instructions + bundled source as one plain-text argument with no
leading `-` flags.
