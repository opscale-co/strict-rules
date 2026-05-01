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

To support this boundary, we require the Aggregate Root to declare its validation rules through the `Validatable` trait from the [`opscale-co/validations`](https://github.com/opscale-co/validations) package:

```php
use Opscale\Validations\Validatable;

class Order extends Model {
    use Validatable;

    // Validatable provides the validation contract for the
    // aggregate root: declare rules() and validation runs
    // before persistence.
}
```

The rule is satisfied if `Validatable` is declared on the class itself **or on any ancestor** in its inheritance chain — so a base `AbstractAggregateRoot extends Model { use Validatable; }` covers every concrete subclass without requiring each one to redeclare the trait.

---

## 🚩 Code Smell

> Saving child models (`OrderItem`) independently of their aggregate root (`Order`).

This bypasses the rules and leads to inconsistent domain states.

---

## 🧪 AST Rules

### 📌 `ModelValidationRule`

- **Purpose:** Ensure all Eloquent models used as Aggregate Roots define a validation mechanism.
- **Description:** Flags Eloquent models that do not use the `Validatable` trait from the `opscale-co/validations` package, taking the inheritance chain into account.
- **Justification:** Validates business invariants before persisting changes using a standardized validation contract owned by Opscale.

| Property     | Value               |
|--------------|---------------------|
| Rule Name    | `ModelValidationRule`|
| Identifier   | `ddd.aggregates.modelValidation` |
| Scope        | Class-level (walks the inheritance chain) |
| Condition    | The class itself or any ancestor MUST use `Opscale\Validations\Validatable` from [`opscale-co/validations`](https://github.com/opscale-co/validations). The match is by FQCN — a homonymous trait in another namespace does not satisfy the rule. |

---

### 📌 `ParentChildTransactionRule`

- **Purpose:** Enforce that child entities are not saved directly.
- **Description:** Flags `save()` calls in `\Models\Repositories` or `\Services` on Eloquent models whose own class — or any ancestor in their inheritance chain — declares a method with a `BelongsTo` (or `MorphTo`) return type. Such entities must be persisted through their aggregate root.
- **Justification:** Child entities must be persisted through their parent aggregate to preserve domain consistency.

| Property     | Value                     |
|--------------|---------------------------|
| Rule Name    | `ParentChildTransactionRule` |
| Identifier   | `ddd.aggregates.parentChildTransaction` |
| Scope        | Method-level inside `\Models\Repositories` and `\Services` |
| Condition    | Disallow `save()` on a parameter whose declared type (or any ancestor) declares a method returning `BelongsTo` or `MorphTo`. Recognition is **return-type only** — the textual presence of a `belongsTo()` call inside a method body is **not** a relationship signal, so domain helpers that use `belongsTo` as a verb are no longer mis-flagged. |
