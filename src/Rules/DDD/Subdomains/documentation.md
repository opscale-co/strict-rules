# DDD Concept: Subdomains

> **Subdomains break down the problem space into smaller, more manageable business contexts.**

---

## 🧠 What It Means

In Domain-Driven Design, a **subdomain** is a distinct area of the overall domain, with its own logic, vocabulary, and models. Subdomains help divide and conquer complexity by:

- Grouping related behaviors and data
- Avoiding overgeneralization
- Enabling focused modeling of business concerns

Each subdomain may include its own entities, services, value objects, and repositories. These form the building blocks of a **bounded context**.

---

## 💡 Considerations

To model subdomains effectively in Laravel, we enforce a **package-oriented architecture**. This means that each subdomain:

- Lives in its own namespace or Laravel package
- Contains all relevant logic: models, services, resources, repositories
- Can be reused across projects or isolated for testing

In projects using Laravel Nova, a subdomain may be implemented as `nova-components`. In other cases, a dedicated Laravel or Laravel Nova package may encapsulate all subdomain logic.

This improves modularity, reduces coupling, and supports scaling the codebase as the business grows.

---

## 🧵 A Data Story Example: Revenue Subdomain

In our influencer merch store, revenue tracking is distinct from order management or inventory.

We encapsulate all revenue logic (calculating influencer shares, commissions, payment statuses) into a dedicated package:

```
packages/
  Revenue/
    src/
      Models/
        InfluencerEarnings.php
      Services/
        EarningsCalculator.php
      Resources/
        InfluencerEarningsResource.php
      RevenueServiceProvider.php
```

This structure ensures the revenue subdomain is:
- Easy to maintain
- Clearly separated from unrelated features
- Testable in isolation

---

## 🚩 Code Smell

> All models and services live under one large `App\Models` namespace.

This leads to high coupling and lack of clear boundaries between domain concepts.

---

## 🧪 AST Rules

### 📌 `BaseNamespaceRule`

- **Purpose:** Ensure Eloquent domain entities live directly under a `\Models` namespace segment, with no subfolders.
- **Description:** Walks every `Class_` declaration in the file and flags any class that extends `Illuminate\Database\Eloquent\Model` (directly or transitively) when the file's namespace does not end with `\Models`. Multi-class files are fully covered: a non-Eloquent helper followed by an Eloquent class no longer hides the second class from the rule.
- **Justification:** Subdomain decomposition relies on a consistent home for entities. A flat layout under `\Models` keeps the boundary obvious and prevents per-aggregate subfolders from drifting into a parallel hierarchy.

| Property     | Value               |
|--------------|---------------------|
| Rule Name    | `BaseNamespaceRule` |
| Identifier   | `ddd.subdomains.baseNamespace` |
| Scope        | Class-level. The rule walks every `Class_` declared in the file. |
| Condition    | The file's namespace MUST end with `\Models`. Eloquent models nested in subfolders under `\Models` (e.g. `\Models\Aggregate\Order`) are flagged. Non-Eloquent classes are never flagged regardless of namespace. |

---

### 📌 `EntityCountRule`

- **Purpose:** Limit the number of concrete Eloquent entities per subdomain to avoid bloat and encourage decomposition.
- **Description:** Implemented as a PHPStan two-phase rule. `EntityCountCollector` records every concrete Eloquent model declared in any analysed file. `EntityCountRule` consumes the collected data via `Rule<CollectedDataNode>`, groups records by file-level namespace, deduplicates by FQCN, and emits one error per subdomain whose entity count exceeds `maxClasses` (default `25`). Each `\Models` namespace is its own subdomain — the rule never lumps multiple subdomains together. Non-concrete classes (interfaces, traits, enums, abstract classes, anonymous, plain helpers) never count.
- **Justification:** A subdomain that grows past ~25 concrete entities is a strong signal it should be split. Using a collector ensures correctness across multi-class files and project-wide aggregation that a per-file rule cannot achieve.

| Property     | Value               |
|--------------|---------------------|
| Rule Name    | `EntityCountRule`   |
| Identifier   | `ddd.subdomains.entityCount` |
| Default      | `maxClasses = 25` (override via NEON `arguments`) |
| Scope        | Project-wide aggregation, grouped by file-level namespace |
| Counted      | Concrete classes that subclass `Illuminate\Database\Eloquent\Model` |
| Not counted  | Abstract classes, interfaces, traits, enums, anonymous classes, plain (non-Eloquent) classes |
| Threshold    | `count(unique fqcns) > maxClasses` (strictly greater) |
| Pairs with   | `Opscale\Rules\DDD\Domain\Helpers\EntityCountCollector` (registered with `phpstan.collector` tag in `rules.ddd.neon`) |
