# DDD Concept: Value Object

> **A Value Object represents a concept by its attributes, not its identity.**

---

## 🧠 What It Means

In Domain-Driven Design, a **Value Object** models something that doesn't have an identity of its own. Two value objects are equal if their data is the same.

Value Objects:
- Are immutable
- Represent a measurable or descriptive aspect of the domain (e.g., Email, Money, Coordinates)
- Should not have identity or side effects

They capture meaning and encapsulate domain logic close to the data they represent.

---

## 💡 Considerations

Laravel supports attribute logic in multiple ways—through **mutators**, **accessors**, and **custom casts**.

To align with DDD, we use **value object classes as casts**. Even though casts are technically for transforming values between storage and retrieval, they offer a clean place to centralize logic via a dedicated class.

This ensures:
- Separation of domain logic from models
- Single-purpose classes per concept
- Reusability and testability of logic

We discourage using mutators or accessors for domain logic to avoid scattering behavior across the model.

---

## 🧵 A Data Story Example: Promotion Discount as a Value Object

In our influencer merch store, we apply promotional discounts during checkout. There is a **specific business rule**: *a discount cannot exceed 20% of the total order value*.

To enforce this consistently, we encapsulate the rule in a value object:

```php
namespace App\Models\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class PromotionDiscount implements CastsAttributes {
    public function get($model, string $key, $value, array $attributes): float {
        return (float) $value;
    }

    public function set($model, string $key, $value, array $attributes): array {
        $orderTotal = $attributes['total'] ?? 0;

        if ($orderTotal <= 0) {
            throw new InvalidArgumentException("Order total must be greater than 0.");
        }

        $maxDiscount = $orderTotal * 0.2;

        if ($value > $maxDiscount) {
            throw new InvalidArgumentException("Discount cannot exceed 20% of the total order.");
        }

        return [$key => (float) $value];
    }
}
```

In the model:

```php
class Order extends Model {
    protected $casts = [
        'discount' => PromotionDiscount::class,
    ];
}
```

Now, any time we apply a discount, this business rule is automatically enforced in one place.

---

## 🚩 Code Smell

> Using accessors/mutators directly in models for domain logic

This mixes responsibilities and makes logic harder to track and reuse.

---

## 🧪 AST Rules

### 📌 `EnforceCastRule`

- **Purpose:** Ensure all value object classes implement the `CastsAttributes` interface.
- **Description:** Flags any class in `App\Models\ValueObjects` that does not implement `CastsAttributes`.
- **Justification:** Encourages the use of cast-based value objects to isolate transformation logic.

| Property     | Value              |
|--------------|--------------------|
| Rule Name    | `EnforceCastRule`  |
| Scope        | Class-level        |
| Condition    | Must implement `CastsAttributes` if under `App\Models\ValueObjects` |

---

### 📌 `NoAccesorMutatorRule`

- **Purpose:** Prevent the use of accessors and mutators inside value object classes.
- **Description:** Flags any value object class that defines methods with `get...Attribute` or `set...Attribute`.
- **Justification:** Keeps all transformation logic in cast methods to promote immutability and consistency.

| Property     | Value                  |
|--------------|------------------------|
| Rule Name    | `NoAccesorMutatorRule` |
| Scope        | Method-level           |
| Condition    | Disallow `get*/set*Attribute` methods in Model classes |
