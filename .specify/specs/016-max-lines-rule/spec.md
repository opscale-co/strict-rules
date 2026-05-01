# Feature Specification: MaxLinesRule — measure class lines, not file lines, and walk multi-class files

**Feature Branch**: `016-max-lines-rule`
**Created**: 2026-05-01
**Status**: Draft

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Block oversized classes (Priority: P1)

As an architect, I want PHPStan to fail when a class definition exceeds a configured number of lines, so that the Single Responsibility Principle stays enforceable through a hard cap.

**Why this priority**: Constitution Article VIII (SRP) — "If a class exceeds ~150 lines, it likely has more than one responsibility." The hard cap is a tripwire, not a substitute for design judgement, but it is the rule's central job. P1.

**Acceptance Scenarios**:

1. **Caso positivo (true positive)** — **Given** a class `Opscale\Models\User` whose declaration spans more than the configured threshold (25 in the test setup), **When** PHPStan analyses the file, **Then** the rule reports exactly one error with the actual class line count.

2. **Caso negativo (true negative)** — **Given** a class `Opscale\Models\ValidSmallUser` whose declaration is at or below the threshold, **When** PHPStan analyses the file, **Then** the rule reports zero errors.

---

### User Story 2 — Don't count `use` statements and file headers as class lines (Priority: P1)

As a developer with a class file that has many `use` imports, license headers, or namespaced constants outside the class body, I expect the rule to measure my CLASS, not my FILE. Today the rule subtracts the FileNode start line from the FileNode end line — it counts everything from the namespace declaration to the last closing brace, including all imports.

**Why this priority**: This is the **false-positive** scenario. A small class can be wrongly flagged because its file happens to have many imports. The fix measures the `Class_`/`Trait_`/`Enum_` node directly.

**Acceptance Scenarios**:

1. **Falso positivo a evitar** — **Given** a class `Opscale\Models\SmallClassWithManyImports` whose declaration body is short (≤ threshold) but whose file has many `use` statements pushing the file's total line count above the threshold, **When** PHPStan analyses the file, **Then** the rule reports zero errors. Today's rule wrongly flags this because it counts file lines instead of class lines.

---

### User Story 3 — Detect every oversized class in a multi-class file (Priority: P2)

As an architect, I want the rule to inspect every class declared in a file. PHP allows multiple class declarations per file; today the rule reads only the first via `getRootNode`, so a fat second class slips through.

**Why this priority**: This is the **false-negative** scenario. With multi-class files, today's rule emits at most one error per file, attributed to the first class — even when both classes exceed the threshold.

**Acceptance Scenarios**:

1. **Falso negativo a evitar** — **Given** a file `Opscale\Models\MultiClassFatClasses` declaring two classes whose bodies both exceed the threshold, **When** PHPStan analyses the file, **Then** the rule reports exactly two errors, one per class, each with its own line count.

---

### Edge Cases

- The rule applies to `Class_`, `Trait_`, and `Enum_` nodes (matching the existing scope of `getRootNode`). All three classlike kinds get counted.
- Anonymous classes (without `namespacedName`) are skipped — they have no FQCN to attribute the error to.
- The default threshold remains `500` lines, sized for production. The test suite uses a tighter `25` threshold for fixture-friendly demonstrations.
- The reported line is the closing brace's line number (the natural pointer for the violation).

## Requirements *(mandatory)*

- **FR-001**: `MaxLinesRule` MUST measure each `Class_`/`Trait_`/`Enum_` node's line count via `node->getEndLine() - node->getStartLine() + 1`. It MUST NOT measure FileNode lines.
- **FR-002**: `MaxLinesRule` MUST iterate every classlike declaration in the file (not just `getRootNode`'s first match).
- **FR-003**: `MaxLinesRule` MUST skip declarations without a resolved `namespacedName` (anonymous classes).
- **FR-004**: The default threshold remains `500` lines. The constructor signature stays `(ReflectionProvider, int $maxLines = 500)`.
- **FR-005**: Diagnostic identifier `solid.srp.maxLines` MUST be preserved.
- **FR-006**: The error message format remains: `Class "<FQCN>" has <N> lines, which exceeds the maximum allowed <T> lines. Consider breaking this class into smaller classes to follow the Single Responsibility Principle.`

## Success Criteria

- **SC-001**: `vendor/bin/pest tests/Rules/MaxLinesTest.php` passes with the four named scenarios.
- **SC-002**: `npm test` continues all-green.
- **SC-003**: Diagnostic identifier remains `solid.srp.maxLines`.

## Assumptions

- Most consumer projects have one class per file; for those the file-level vs class-level difference is small (a few lines for the namespace + imports). The fix matters mostly for outlier files with heavy headers or multi-class layouts, but the change is structurally cleaner regardless.
- Multi-class fixtures need an `autoload-dev.classmap` entry in `composer.json` because PSR-4 cannot autoload multiple classes from one file.
