# DDD Concept: Domain Service

> **A Domain Service is a stateless operation that coordinates domain logic involving multiple entities or aggregates.**

---

## 🧠 What It Means

When a domain operation doesn't naturally belong to a single entity or value object, we extract it to a **Domain Service**. This service represents a business capability that involves **multiple domain models** and enforces rules across them.

Domain Services:
- Are part of the domain layer
- Have no internal state (they are stateless)
- Focus on business operations, not infrastructure or side effects

---

## 🧵 A Data Story Example: Order Checkout

In our influencer merch store, when a customer checks out, we need to:
- Validate the order
- Reserve stock
- Charge the customer
- Notify logistics

These steps span multiple domain concepts (Order, Inventory, Payment, Fulfillment). Instead of bloating one model with all this logic, we create a service:

```php
class CheckoutService {
    public function __construct(
        private InventoryService $inventory,
        private PaymentGateway $payment,
        private FulfillmentHandler $fulfillment,
    ) {}

    public function checkout(Order $order): void {
        $order->validate();
        $this->inventory->reserve($order);
        $this->payment->charge($order);
        $this->fulfillment->dispatch($order);
    }
}
```

This service coordinates multiple domain actors and enforces rules at the orchestration level. It lives in the domain layer (not the infrastructure) and can be easily tested.

---

## 🚩 Code Smell

> Application services or controllers containing large blocks of domain logic involving multiple models. This should only happen in Transformation layer.

Domain Services should own this complexity to ensure separation of concerns.

---

## 🧪 AST Rules

### 📌 `ComplexLogicRule`

- **Purpose:** Enforce that complex domain logic spanning multiple models lives only inside domain services (typically your `App\Services`).
- **Description:** Allows multiple Eloquent model references only inside classes recognized as services.
- **Justification:** Keeps orchestration logic out of models and helpers, centralizing it in dedicated services.

| Property     | Value               |
|--------------|---------------------|
| Rule Name    | `ComplexLogicRule`  |
| Scope        | Class-level         |
| Condition    | Multiple model dependencies allowed only in `*Service` classes |
