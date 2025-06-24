# SOLID Principle: Liskov Substitution Principle (LSP)

> **Derived classes must be substitutable for their base classes.**

---

## 🧠 What It Means

The Liskov Substitution Principle ensures that subclasses can be used wherever their parent class is expected—**without breaking functionality**. That means if a parent method sets up expected behavior, the subclass must preserve that behavior or explicitly extend it without removing it.

---

## 🧵 A Data Story Example

In our influencer merch store, suppose we have a base class for notifying customers after order fulfillment:

```php
class OrderNotifier {
    public function notify(Order $order): void {
        // Log notification event
        $this->logEvent($order);
    }

    protected function logEvent(Order $order): void {
        // Append to audit log
    }
}
```

Now imagine a subclass overrides the `notify()` method:

```php
class WhatsAppNotifier extends OrderNotifier {
    #[\Override]
    public function notify(Order $order): void {
        // Send WhatsApp message
        // (Does NOT call parent::notify)
    }
}
```

This breaks substitutability. Any system relying on `OrderNotifier` expects that a notification is logged. By skipping `parent::notify()`, the subclass removes part of the contract.

The right way? Subclasses that override behavior **should call `parent::`** to retain the base class guarantees:

```php
class WhatsAppNotifier extends OrderNotifier {
    #[\Override]
    public function notify(Order $order): void {
        // Extend behavior
        parent::notify($order);
        $this->sendWhatsApp($order);
    }

    private function sendWhatsApp(Order $order): void {
        // Send WhatsApp message
    }
}
```

Now, substituting `OrderNotifier` with `WhatsAppNotifier` does not break expectations: the audit log still works.

---

## 🚩 Code Smell

> Subclasses that override methods and skip the base logic completely—especially when the method is marked `#[\Override]` or `@overridable`.

This indicates a broken contract between base and child.

---

## 🧪 AST Rules

### 📌 `ParentCallRule`

- **Purpose:** Enforce that overridden methods preserve base behavior.
- **Description:** Any method annotated with `#[\Override]` or `@overridable` must call `parent::method()`.
- **Justification:** Ensures the subclass respects the base contract and maintains substitutability.

### 🔧 Rule Summary

| Property     | Value              |
|--------------|--------------------|
| Rule Name    | `ParentCallRule`   |
| Scope        | Method-level       |
| Condition    | Methods with `#[\Override]` or `@overridable` must include `parent::` call |
