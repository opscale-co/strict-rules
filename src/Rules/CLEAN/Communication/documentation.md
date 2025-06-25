# Clean Architecture Layer: Communication

> **Handles event-based notification of changes within the domain.**

---

## 🧠 What It Means

The **Communication layer** is responsible for emitting and responding to domain events. It provides a decoupled mechanism for propagating information when something happens, without directly triggering business logic.

This layer includes:
- Observers that listen to Eloquent model events
- Domain events that represent something meaningful happening (e.g., `OrderPaid`, `ProductReturned`)

It enables **upward communication** in Clean Architecture—higher layers can react to these events independently.

---

## 🌟 Considerations in Laravel

In Laravel, this layer typically includes:

- `App\Observers\` — Classes that observe model lifecycle events
- `App\Events\` — Domain-specific events triggered across the system

This layer should not implement business rules. Observers and Events **inform**, not **decide**. If complex behavior is needed, delegate to a Service or dispatch a Job.

---

## 🧵 A Data Story Example: Order Paid Event

```php
class OrderObserver {
    public function updated(Order $order): void {
        if ($order->isPaid()) {
            event(new OrderPaid($order));
        }
    }
}

class OrderPaid {
    public function __construct(public Order $order) {}
}
```

This example shows how an observer detects a domain change (an order becomes paid) and emits an event. The event doesn’t know or care who handles it. A listener in another layer (e.g. orchestration) might pick it up and act accordingly.

---

## 🚀 Allowed Imports

| Type      | Allowed Namespaces                                                                 |
|-----------|--------------------------------------------------------------------------------------|
| Project   | `App\Events\`, `App\Observers\`, `App\Models\`                                |
| Framework | `Illuminate\Foundation\Events`, `Illuminate\Queue`, `Illuminate\Broadcasting`, `Illuminate\Contracts` |
| Facades   | `Broadcast`, `Event`                                                                |

The Communication layer can depend only on the **Representation layer**. It should never call Services, Jobs, or HTTP controllers.

---

## 🚩 Code Smells

- An Observer or Event class that instantiates or uses a Service
- Logic-heavy event handlers
- Calling `Notification::send()` directly from an observer

---

## 🧪 AST Rules

### 📌 AST Rule: `CommunicationLayerRule`

- **Purpose:** Enforce allowed imports for the Communication layer
- **Description:** Allows importing only from Representation layer, approved framework namespaces, and facades
- **Justification:** Ensures events and observers remain lightweight and decoupled

| Property     | Value                      |
|--------------|----------------------------|
| Rule Name    | `CommunicationLayerRule`   |
| Scope        | File-level                 |
| Condition    | Allow only specific class and facade imports based on Clean Architecture assumptions |
