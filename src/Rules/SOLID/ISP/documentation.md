# SOLID Principle: Interface Segregation Principle (ISP)

> **A class should not be forced to implement methods it does not use.**

---

## 🧠 What It Means

The Interface Segregation Principle (ISP) promotes **focused, specific interfaces**. A class should only implement what it truly needs. Large, catch-all interfaces lead to bloated, fragile implementations where many methods are unused or poorly implemented.

---

## 🧵 A Data Story Example

In our influencer merch store, suppose we define this interface:

```php
interface FulfillmentHandler {
    public function deliver(Order $order): void;
    public function schedulePickup(Order $order): void;
    public function arrangeInternationalShipping(Order $order): void;
}
```

Now we have a class for local store pickups:

```php
class LocalPickupHandler implements FulfillmentHandler {
    public function deliver(Order $order): void {
        throw new \LogicException("Local pickup does not support delivery");
    }

    public function schedulePickup(Order $order): void {
        // Confirm pickup time
    }

    public function arrangeInternationalShipping(Order $order): void {
        throw new \LogicException("Local pickup does not support international shipping");
    }
}
```

This violates ISP. The class is forced to implement methods it doesn’t support.

The right way? Split interfaces into smaller, purpose-specific contracts:

```php
interface DeliveryHandler {
    public function deliver(Order $order): void;
}

interface PickupScheduler {
    public function schedulePickup(Order $order): void;
}

interface InternationalShippingHandler {
    public function arrangeInternationalShipping(Order $order): void;
}
```

Now classes implement **only what they need**:

```php
class LocalPickupHandler implements PickupScheduler {
    public function schedulePickup(Order $order): void {
        // Confirm pickup time
    }
}
```

---

## 🚩 Code Smell

> Interfaces with unrelated or excessive method requirements.

If you find yourself throwing exceptions or returning dummy data just to satisfy an interface, consider splitting it.

---

## 🧪 AST Rules

### 📌 `EnforceImplementationRule`

- **Purpose:** Ensure methods declared by interfaces are **meaningfully implemented**.
- **Description:** Flags implementations that throw generic exceptions or return default/no-op values in interface methods.
- **Justification:** Prevents interface bloat and encourages focused, intentional design.

### 🔧 Rule Summary

| Property     | Value                      |
|--------------|----------------------------|
| Rule Name    | `EnforceImplementationRule`|
| Scope        | Method-level               |
| Condition    | Interface methods must not throw \Exception or return dummy values |
