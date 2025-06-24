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
- **Description:** Flags any class file exceeding **500 lines**.
- **Justification:** Classes with multiple responsibilities tend to grow uncontrollably. Keeping them short helps ensure separation of concerns.

### 🔧 Rule Summary

| Property     | Value              |
|--------------|--------------------|
| Rule Name    | `MaxLinesRule`     |
| Scope        | File-level         |
| Threshold    | 500 lines          |
