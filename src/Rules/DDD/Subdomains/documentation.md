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

- **Purpose:** Ensure subdomain classes live in their appropriate package or namespace.
- **Description:** Flags any Eloquent model not located in a subdomain-aligned namespace.
- **Justification:** Reinforces modular boundaries and bounded context clarity.

| Property     | Value               |
|--------------|---------------------|
| Rule Name    | `BaseNamespaceRule` |
| Scope        | Class-level         |
| Condition    | Model must be under a package-aligned namespace |

---

### 📌 `EntityCountRule`

- **Purpose:** Limit the number of classes in a single subdomain to avoid bloat.
- **Description:** Flags subdomains that exceed a predefined number of classes.
- **Justification:** Encourages decomposition and clarity within subdomain scopes.

| Property     | Value               |
|--------------|---------------------|
| Rule Name    | `EntityCountRule`   |
| Scope        | Package-level       |
| Condition    | Subdomain should not exceed configured entity limit |
