# SOLID Principle: Open/Closed Principle (OCP)

> **Software entities should be open for extension but closed for modification.**

---

## 🧠 What It Means

The Open/Closed Principle (OCP) encourages designing modules that allow behavior to be **extended** without changing their **existing code**. This protects core logic from unintended side effects and reduces regression risk.

## 💡 Considerations

In PHP, all methods are **virtual** by default—meaning any subclass can override them, even unintentionally. Without clear boundaries, critical logic may be accidentally modified through inheritance.

A good approach to mitigate this is to **invert the default pattern**: make all methods `final` by default, and only explicitly mark methods as extendable when necessary. This makes the intent clear—**what should and should not be overridden**—which reinforces encapsulation and protects core invariants.

By doing this, you:

- Prevent accidental extension of logic that shouldn't change.
- Make extension points explicit and easier to maintain.
- Promote composition over inheritance for flexibility.

---

## 🧵 A Data Story Example

In our influencer merch store, we have a class that calculates how much commission an influencer earns from a sale:

```php
class CommissionCalculator {
    public function calculate(Order $order): float {
        // Standard 10% commission
        return $order->total * 0.10;
    }
}
```

Now imagine someone extends this class:

```php
class CustomCommissionCalculator extends CommissionCalculator {
    public function calculate(Order $order): float {
        // Changes commission logic
        return $order->total * 0.25;
    }
}
```

This completely **replaces** the original logic—without any explicit signal. A junior developer could unintentionally introduce business-critical bugs just by overriding the wrong method.

The right way? Make critical methods `final` and expose extension points through composition:

```php
class CommissionCalculator {
    final public function calculate(Order $order): float {
        return $this->baseRate() * $order->total;
    }

    protected function baseRate(): float {
        return 0.10;
    }
}
```

To customize behavior, a subclass may override the protected hook **if explicitly allowed**:

```php
class PremiumCommissionCalculator extends CommissionCalculator {
    #[\Override]
    protected function baseRate(): float {
        return 0.25;
    }
}
```

This makes the override **intentional and explicit**, and ensures the structure of the main algorithm stays protected.

---

## 🚩 Code Smell

> Core logic that changes behavior just because a subclass overrides a method—often without intention.

---

## 🧪 AST Rules

### 📌 `FinalMethodRule`

- **Purpose:** Protect core logic by making methods non-overridable unless explicitly marked.
- **Description:** Ensures all public and protected methods are declared `final`, unless annotated with `#[\Override]` or `@overridable`.
- **Justification:** In PHP, methods are virtual by default. This rule reduces the risk of unintended polymorphism and enforces deliberate extension points.

### 🔧 Rule Summary

| Property     | Value              |
|--------------|--------------------|
| Rule Name    | `ConditionalOverrideRule`  |
| Scope        | Method-level       |
| Condition    | Must be `final` unless explicitly marked `@overridable` or `#[\Override]` |
