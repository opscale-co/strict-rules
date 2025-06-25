# DDD Concept: Domain

> **The Domain is the core of your software—the part that expresses your business rules and logic.**

---

## 🧠 What It Means

The **Domain** is the heart of Domain-Driven Design. It represents the **problem space**: the business knowledge, rules, and behavior that your system must model.

A domain model:
- Encodes business logic
- It's designed with input from domain experts
- It's free from technical or infrastructural concerns

The goal is to focus purely on **what the business does**, not on how data is stored or APIs are called.

---

## 🧵 A Data Story Example: Influencer Eligibility

In our influencer merch store, influencers must meet certain criteria to be eligible for higher commissions.

This is business logic, so we want to model it clearly:

```php
class InfluencerEligibility {
    public function __construct(private int $followers, private bool $verified) {}

    public function isEligible(): bool {
        return $this->followers >= 10000 && $this->verified;
    }
}
```

This class captures a clear domain rule with no side effects or loops. If the logic grows more complex, we extract strategies.

Avoid this:
```php
public function isEligible(): bool {
    foreach ($this->getRecentPosts() as $post) {
        if ($post->engagementRate() > 0.2) return true;
    }
    return false;
}
```

Loops and procedural logic in domain models are signs that logic needs to be delegated.

---

## 🚩 Code Smell

> Domain models contain loops, multiple conditionals, or deeply nested logic.

This makes them hard to reason about, test, or refactor cleanly.

---

## 🧪 AST Rules

### 📌 `NoStatementsLogicRule`

- **Purpose:** Keep domain models focused and declarative.
- **Description:** Flags loop constructs (`if`, `switch`, `foreach`, `for`, `while`) and complex logic structures inside domain classes.
- **Justification:** Prevents bloated domain logic and encourages delegation to strategy or service layers.

| Property     | Value                  |
|--------------|------------------------|
| Rule Name    | `NoStatementsLogicRule`|
| Scope        | Method-level           |
| Condition    | Disallow loops and excessive control structures in domain models |
