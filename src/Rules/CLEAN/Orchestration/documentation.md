# Clean Architecture Layer: Orchestration

> **Coordinates workflows, dispatches jobs, and handles asynchronous operations.**

---

## 🧠 What It Means

The **Orchestration layer** manages the flow of processes. It's responsible for sequencing steps, triggering actions, and reacting to domain events by invoking services or dispatching background work.

This includes:
- Jobs for background processing (e.g., sending emails, processing orders)
- Notifications for sending alerts via email, SMS, etc.
- Listeners that respond to domain events

It does **not** contain business logic. It simply coordinates execution.

---

## 💡 Considerations in Laravel

This layer typically includes:

- `App\Jobs\` — Queueable tasks
- `App\Listeners\` — Responders to events
- `App\Notifications\` — Channel-agnostic notification classes

These components may invoke services from the Transformation layer but should not contain domain rules directly.

---

## 🧵 A Data Story Example: Order Confirmation

```php
class SendOrderConfirmationEmail implements ShouldQueue {
    public function __construct(public Order $order) {}

    public function handle(): void {
        Notification::route('mail', $this->order->customer_email)
            ->notify(new OrderConfirmed($this->order));
    }
}
```

This job coordinates notification after an order is confirmed. The logic of "what a confirmed order means" should live in a service, not here.

---

## 🚀 Allowed Imports

| Type      | Allowed Namespaces                                                                         |
|-----------|----------------------------------------------------------------------------------------------|
| Project   | `App\Jobs\`, `App\Listeners\`, `App\Notifications\`, `App\Models\`, `App\Events\` |
| Framework | `Illuminate\Bus`, `Illuminate\Contracts`, `Illuminate\Foundation\Bus`, `Illuminate\Notifications`, `Illuminate\Queue` |
| Facades   | `Bus`, `Concurrency`, `Mail`, `Notification`, `Pipeline`, `Queue`, `Redis`, `Schedule`      |

This layer can use **Transformation**, **Communication**, and **Representation** layers, but must avoid directly interacting with Controllers or UI.

---

## 🚩 Code Smells

- Writing conditional business logic in listeners or jobs
- Accessing `Request` or user input
- Calling views or returning responses

---

## 🧪 AST Rules

### 📌 AST Rule: `OrchestrationLayerRule`

- **Purpose:** Enforce allowed imports for the Orchestration layer
- **Description:** Restricts usage to Jobs, Listeners, Notifications, and approved dependencies
- **Justification:** Preserves separation of concerns by keeping coordination distinct from logic or presentation

| Property     | Value                         |
|--------------|-------------------------------|
| Rule Name    | `OrchestrationLayerRule`      |
| Scope        | File-level                    |
| Condition    | Allow only specific class and facade imports based on Clean Architecture assumptions |
