# SOLID Principle: Single Responsibility Principle (SRP)

> **A class should have only one reason to change.**

---

## 🧠 What It Means

The Single Responsibility Principle (SRP) is about **clarity and focus**. Each class should encapsulate a single business concern. When classes take on multiple responsibilities, they become harder to maintain, understand, and test.

---

## 🧵 A Data Story Example

In our influencer merch store, imagine an `OrderManager` class that:

- Validates shipping addresses  
- Calculates influencer commissions  
- Applies discount codes  
- Sends delivery notifications  
- Logs order events

This class mixes logistics, finance, marketing, and infrastructure responsibilities—violating SRP.

Instead, extract responsibilities into dedicated components:

- `AddressValidator`
- `CommissionCalculator`
- `DiscountApplier`
- `DeliveryNotifier`
- `OrderLogger`

Each class has a **single reason to change**, aligned with a business rule or policy.

---

## 🚩 Code Smell

> **Overgrown classes** that act as catch-alls.

If your class description contains “and” more than once, it likely has too many responsibilities:
> “This class processes orders **and** sends notifications **and** applies discounts...”

---

## 🧪 AST Rules

### 📌 `MaxLinesRule`

- **Purpose:** Enforces SRP by restricting class length.
- **Description:** For every `Class_`, `Trait_`, or `Enum_` declaration in a file, measures the lines from the declaration line to its closing brace and flags those that exceed the threshold. The measurement is class-level — file-level imports, license headers, and other classlikes in the same file do not affect the count. Multi-class files are fully covered: every classlike that exceeds the threshold gets its own error.
- **Justification:** Classes with multiple responsibilities grow. Keeping each class small forces decomposition. Class-level measurement avoids the false positive where a small class is wrongly flagged because its file has many imports.

### 🔧 Rule Summary

| Property     | Value              |
|--------------|--------------------|
| Rule Name    | `MaxLinesRule`     |
| Identifier   | `solid.srp.maxLines` |
| Scope        | Per `Class_` / `Trait_` / `Enum_` declaration. Walks every classlike in the file. |
| Threshold    | 500 lines (default; configurable via constructor argument) |
| Counted      | Lines from the classlike's declaration to its closing brace, inclusive |
| Not counted  | File header, namespace declaration, `use` imports, other classlikes in the same file |
