# TODO

## Intermittent empty pages in the functional tests (CI)

The functional test (`tests/functional/confirm_bypass_test.php`) sometimes fails in CI with:

```
Output found before DOCTYPE specification.
Failed asserting that '' starts with "<!DOCTYPE".
```

The empty page comes from **phpBB's own test framework steps**, not from Ban Hammer code:

- 09/27/2026, PR #37 runs: at the framework's `login()` (GET `ucp.php`), once on MSSQL 2019 and once on SQLite; other runs of the same code passed.
- 09/27/2026, `master` after #38: at the framework's `logout()` inside `install_ext()` (called from `parent::setUp()`), on MSSQL 2022, twice in a row. The identical code passed every job, MSSQL 2022 included, in #38's own final run.

The same code both passes and fails, on different databases and at different framework steps, so this looks like a problem in the CI test environment: phpBB's test web server returns an empty body, most likely a PHP fatal error whose output is hidden. It can't be confirmed yet because the CI run doesn't keep the PHP or web server error logs.

**Next step:** get the PHP error log out of a failing run, for example with a small separate workflow that runs the same functional tests and uploads the PHP and nginx error logs as an artifact on failure. Then fix the actual cause, or report it upstream to [phpbb-extensions/test-framework](https://github.com/phpbb-extensions/test-framework) if it's in the test environment.

Until then, rerunning the failed job is enough to tell a flaky run from a real failure.
