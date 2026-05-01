# SOLID Principle: Dependency Inversion Principle (DIP)

> **High-level modules should not depend on low-level modules. Both should depend on abstractions.**

---

## 🧠 What It Means

The Dependency Inversion Principle (DIP) promotes decoupling. High-level components (like controllers, services, or use cases) should not depend directly on concrete implementations of other services or infrastructure. Instead, they should rely on **interfaces or contracts**, which can be swapped or mocked without rewriting logic.

---

## 💡 Considerations

Laravel supports dependency injection out of the box, even with concrete classes. For example, it's common (and acceptable) to inject framework services like `Request`, `Response`, or `Log` directly.

**However**, in the **transformation layer** (typically your `App\Services`), it's best to depend on **interfaces only**. This ensures that:

- Your core logic can be tested in isolation
- Implementations can be swapped freely
- You're not tightly coupled to specific service classes

This approach enforces **interface-based dependency injection** specifically in the **transformation layer**. In controllers, middlewares, jobs, and other layers, using concrete classes from the framework is acceptable.

---

## 🧵 A Data Story Example: Service Coupling

Imagine we have a `CheckoutService` that calls a concrete `PaymentService` directly:

```php
class CheckoutService {
    public function __construct(
        private PaymentService $payment
    ) {}

    public function checkout(Order $order): void {
        $this->payment->charge($order);
    }
}
```

This tightly couples `CheckoutService` to a specific payment implementation.

If later we want to switch to `StripePaymentService` or `FakePaymentService` for testing, we would need to modify this service.

The right way? Extract an interface and rely on it:

```php
interface PaymentInterface {
    public function charge(Order $order): void;
}

class PaymentService implements PaymentInterface {
    public function charge(Order $order): void {
        // Real payment logic
    }
}
```

Update the `CheckoutService`:

```php
class CheckoutService {
    public function __construct(
        private PaymentInterface $payment
    ) {}

    public function checkout(Order $order): void {
        $this->payment->charge($order);
    }
}
```

Register the implementation in a service provider:

```php
public function register(): void {
    $this->app->bind(PaymentInterface::class, PaymentService::class);
}
```

Now `CheckoutService` is **decoupled from the implementation**, and we can inject a mock, a fake, or a third-party payment provider without modifying the service logic.

---

## 🚩 Code Smell

> Injecting or instantiating concrete classes instead of interfaces in your service constructors.

This couples your services directly and makes swapping or testing implementations harder.

---

## 🧪 AST Rules

### 📌 `DisallowInstantiationRule`

- **Purpose:** Enforce DIP by disallowing direct instantiation of services inside business logic. Dependencies should be injected, not constructed.
- **Description:** Walks every classlike (`Class_`, `Trait_`, `Enum_`) declared in the file. For each non-constructor method, recursively finds every `new ClassName(...)` expression and checks the instantiated FQCN against several allow paths:
  1. PHP built-in classes (`\Exception`, `\RuntimeException`, ...).
  2. A fixed list of canonical Laravel / Carbon classes (`Illuminate\Support\Collection`, `Carbon\Carbon`, ...).
  3. **Subclasses** of: `Illuminate\Database\Eloquent\Model`, `Illuminate\Mail\Mailable`, `Illuminate\Notifications\Notification`, `Illuminate\Http\Resources\Json\JsonResource`. Any project Model, Mailable, Notification or API Resource is recognised by reflection.
  4. Heuristic name suffixes: `DTO`, `ValueObject`, `Value`, `Data`, `Request`, `Response`, `Event`.
  5. `self`, `parent`, `static`.
- **Justification:** Promotes inversion of dependencies for service classes while leaving alone the canonical Laravel patterns where `new` is the right choice (creating a domain entity, sending a Mailable, dispatching a Notification, formatting an API Resource).

| Property     | Value                      |
|--------------|----------------------------|
| Rule Name    | `DisallowInstantiationRule`|
| Identifier   | `solid.dip.disallowInstantiation` |
| Scope        | Per non-constructor method, walking every classlike in the file. |
| Skipped      | `__construct`, PHP built-ins, the named-class allow list, subclasses of Model / Mailable / Notification / JsonResource, the suffix list, `self`/`parent`/`static`. |
| Condition    | Disallow `new ClassName()` for everything else. |
