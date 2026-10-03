# TODO

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
