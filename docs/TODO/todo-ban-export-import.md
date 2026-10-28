# Ban export/import

Step 4 of the planned features in [docs/TODO.md](../TODO.md). Requested on phpBB.com in [Export/Import banned email list](https://www.phpbb.com/community/viewtopic.php?t=2669812): someone who runs several boards wants to copy their ban list from one board to the others. Today that means a manual SQL query in phpMyAdmin, then pasting the result into the ACP email ban page.

Agreed so far: CSV, an ACP page only admins with phpBB's `a_ban` permission can use, and phpBB 3.3.19+ and 4.0 both supported.

## ACP page

- A new **Export/import bans** mode in Ban Hammer's ACP module, added by a migration (`module.add`), for admins with phpBB's own `a_ban` permission.
- Export and import on the same page, each in its own fieldset.

## Export

- Choose which ban types to include: username, email, IP.
- One CSV file, UTF-8, with a header row, named like `banhammer-bans-2026-10-20.csv` (no `/` in the date).
- Columns: `type` (`user`, `email`, `ip`), `value`, `start`, `end`, `reason`, `reason_shown`.
  - `value` for username bans is the **username**, not the user id, so the file means the same thing on another board.
  - `start` and `end` are ISO 8601 UTC; an empty `end` means permanent.
  - `reason` is the staff reason, `reason_shown` the one the banned user sees.
- Only active bans; expired ones are left out.
- phpBB 3.3 exclusions (`ban_exclude = 1`) are left out: phpBB 4.0 has no exclusions, so they can't round-trip.
- Guard against CSV formula injection: prefix values that start with `=`, `+`, `-` or `@` with `'`.
- Read from 3.3's `banlist` table or 4.0's `bans` table, like the ban list page does.

## Import

1. **Upload** the CSV. Cap the file size and row count, and read it with `fgetcsv()`, never by splitting lines by hand.
2. **Preview**: a table of what would happen to each row, with counts:
   - **New ban**: will be added.
   - **Already banned**: the board already has this ban, so it is skipped.
   - **Expired**: `end` is in the past, so it is skipped.
   - **Unknown user**: a username ban for a name that doesn't exist on this board, so it is skipped.
   - **Invalid**: bad type, malformed email or IP, or a bad date, with the reason shown.
3. **Confirm** (with a form key) to add the new bans; **Cancel** to drop the upload.

Details:

- Bans are added through `core\ban_helper`, so they go through phpBB's own ban code on 3.3 and 4.0 and are logged like any other ban. The helper needs a variant that takes an end time instead of a length in minutes.
- The ban start is the import time; the original start is shown in the preview but not kept.
- One admin log entry for the import, with the counts.
- Between preview and confirm, keep the uploaded file in Ban Hammer's own folder in the board's `store/` (`store/phpbbmodders_banhammer/imports/`, with a random name), never under `ext/`. Make the folder when needed, protect it from web access, delete the file on confirm or cancel, and have a cron task delete leftovers older than a day. Show admins a clear message if the folder can't be written.

## Tests

- Unit tests for reading and checking CSV rows: each outcome above, the formula-injection guard, and both ban tables.
- A functional test on 3.3 and 4.0: export from a board with one ban of each type, import into a board without them, and check the result; import again and check everything is skipped as already banned.
- Board checks on both seed-forum boards before the PR.
