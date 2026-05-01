# Clean Architecture Layer: Transformation

> **Applies business rules, processes data, and handles exceptions.**

---

## 🧠 What It Means

The **Transformation layer** is the heart of your business logic. It transforms raw inputs (from forms, APIs, events, etc.) into meaningful results by applying the core rules and processes of your domain.

This includes:
- Services (e.g., `CheckoutService`, `DiscountCalculator`)
- Domain-specific exceptions
- Contracts that abstract external services (e.g., `PaymentGatewayInterface`)

This layer does **not** handle infrastructure or UI logic. It focuses on enforcing business invariants.

---

## 💡 Considerations in Laravel

In Laravel, this includes:
- `App\Services\` — Domain logic and reusable operations
- `App\Exceptions\` — Application-specific exception handling
- `App\Contracts\` — Interfaces to abstract dependencies

Laravel’s flexibility allows injecting many concrete classes. However, to enforce decoupling, services in this layer should depend on **interfaces**, not implementations—especially when consuming APIs or third-party services.

---

## 🧵 A Data Story Example: Discount Application

```php
class DiscountService {
    public function apply(Order $order): float {
        $total = $order->total;
        $discount = $order->hasPromo() ? 0.20 : 0.0;

        if ($discount > 0.20) {
            throw new MaxDiscountExceededException("Promo exceeds business rule limits.");
        }

        return $total * (1 - $discount);
    }
}
```

This service encapsulates a business rule: discounts cannot exceed 20%. It processes input, applies logic, and returns a result.

---

## 🚀 Allowed Imports

| Type      | Allowed Namespaces                                                     |
|-----------|------------------------------------------------------------------------|
| Project   | Any class under `\Services\`, `\Exceptions\`, `\Contracts\`, or any class in lower layers (`\Models\`, `\Observers\`). The root namespace is package-agnostic — `Opscale\Services\Foo`, `App\Services\Foo`, `Vendor\Package\Services\Foo` all detect as layer 3. |
| Framework | `Illuminate\Contracts\`, `Illuminate\Foundation\`, `Illuminate\Http\Client\`, `Illuminate\Support\Arr`, `Illuminate\Support\Collection`, `Illuminate\Support\Number`, `Illuminate\Support\Str` (covers `Stringable` too), `Symfony\Component\` |
| External  | `Carbon\`, `Lorisleiva\`, `Ramsey\Uuid\`, `Spatie\` |
| Facades   | `App`, `Cache`, `Config`, `Crypt`, `Exceptions`, `File`, `Http`, `Log`, `Storage` |

The Transformation layer can depend on **Representation** and **Communication** layers, but not on Jobs, Controllers, or Nova components.

---

## 🚩 Code Smells

- Using `Request` or `Response` directly inside a service
- Calling `View::make()` or rendering HTML
- Tight coupling to third-party APIs without abstraction

---

## 🧪 AST Rules

### 📌 AST Rule: `TransformationLayerRule`

- **Purpose:** Enforce allowed imports for the Transformation layer
- **Description:** Allows importing only from designated layers and specific framework namespaces
- **Justification:** Keeps domain logic focused, testable, and free of UI or orchestration concerns

| Property     | Value                        |
|--------------|------------------------------|
| Rule Name    | `TransformationLayerRule`    |
| Scope        | File-level                   |
| Condition    | Allow only specific class and facade imports based on Clean Architecture assumptions |
