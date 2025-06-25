# DDD Concept: Aggregates

> **An Aggregate is a cluster of domain objects treated as a single unit for data changes.**

---

## 🧠 What It Means

In Domain-Driven Design (DDD), an **Aggregate** is a boundary around a group of related entities. It ensures that all operations that change data are performed through a single entry point—the **Aggregate Root**. This protects business invariants and ensures transactional consistency.

The Aggregate Root is the only member of the cluster that external objects are allowed to hold references to.

---

## 🧵 A Data Story Example: Orders and Order Items

In our influencer merch store, an `Order` is composed of multiple `OrderItem`s. Each item represents a product being purchased.

```php
class Order extends Model {
    public function items() {
        return $this->hasMany(OrderItem::class);
    }

    public function addItem(Product $product, int $quantity): void {
        $this->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }
}

class OrderItem extends Model {
    public function order() {
        return $this->belongsTo(Order::class);
    }
}
```

We should **not** allow `OrderItem` to be saved or modified directly from the outside. Instead, all changes to items should go through the `Order` Aggregate Root using methods like `addItem()`.

This ensures that:
- Items are only persisted in valid order contexts
- Business rules (e.g., total validation) are enforced

To support this boundary, we also require a `validate()` method in the Aggregate Root:

```php
public function validate(string $key): array {
    return [
        'items' => ['required', 'gt:1']
    ];
}
```

---

## 🚩 Code Smell

> Saving child models (`OrderItem`) independently of their aggregate root (`Order`).

This bypasses the rules and leads to inconsistent domain states.

---

## 🧪 AST Rules

### 📌 `ModelValidationRule`

- **Purpose:** Ensure all Eloquent models used as Aggregate Roots define a validation mechanism.
- **Description:** Flags Eloquent models that do not implement a `validate(): void` method.
- **Justification:** Validates business invariants before persisting changes.

| Property     | Value               |
|--------------|---------------------|
| Rule Name    | `ModelValidationRule`|
| Scope        | Class-level         |
| Condition    | Must define `public function validate(): void` |

---

### 📌 `ParentChildTransactionRule`

- **Purpose:** Enforce that child entities are not saved directly.
- **Description:** Flags usage of `save()` or `create()` on child models that have a `belongsTo` relationship.
- **Justification:** Child entities must be persisted through their parent aggregate to preserve domain consistency.

| Property     | Value                     |
|--------------|---------------------------|
| Rule Name    | `ParentChildTransactionRule` |
| Scope        | Method-level              |
| Condition    | Disallow save/create on child with `belongsTo()` relationship |
