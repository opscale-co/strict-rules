# DDD Concept: Entities

> **An Entity is an object defined by its identity, not just its attributes.**

---

## 🧠 What It Means

Entities in Domain-Driven Design (DDD) are objects that represent concepts with a **distinct and consistent identity**. Unlike value objects, which are defined by their attributes, an entity is defined by who it is over time—even as its data changes.

An entity must have:
- A unique identifier
- Identity consistency across its lifecycle
- Behavior relevant to the domain

The identifier should be unique **within the context of the system**, and ideally **decoupled from infrastructure concerns** such as persistence or database design.

---

## 💡 Considerations

Laravel models typically use an auto-incrementing integer ID by default. While simple, this approach has limitations:
- IDs are not globally unique across entities
- Collisions or assumptions may arise when working across bounded contexts
- Auto-incrementing IDs couple your entities to the database schema

To align better with DDD, Laravel supports ULIDs via the `UseUlids` trait. ULIDs offer:
- Globally unique and sortable identifiers
- Decoupling from infrastructure constraints
- Better interoperability across services and contexts

We recommend enforcing the use of `UseUlids` for domain entities that extend Laravel’s Eloquent `Model`.

---

## 🧵 A Data Story Example: Order as an Entity

In our influencer merch store, an `Order` is an entity. Its items, status, and totals may change—but its identity remains stable and must be unique within the system.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\UseUlids;

class Order extends Model {
    use UseUlids;
}
```

By using `UseUlids`, the `Order` class gains identity management that supports consistency, distribution, and auditability.

---

## 🚩 Code Smell

> Using auto-incrementing integers as identifiers for domain entities

This exposes database internals to the domain and limits flexibility in distributed systems or complex integration scenarios.

---

## 🧪 AST Rules

### 📌 `EnforceUlidsRule`

- **Purpose:** Ensure that all domain entities use ULIDs for identity.
- **Description:** Flags any Eloquent model class under `\Models\*` that does not declare `Illuminate\Database\Eloquent\Concerns\HasUlids` either directly (in any of its `use ...;` statements) or transitively through any ancestor in its inheritance chain. When the trait is present, the rule additionally rejects classes that neutralise it by declaring `public $incrementing = true;` or `protected $keyType` set to a value other than `'string'`.
- **Justification:** Enforces domain identity consistency and avoids reliance on auto-incrementing primary keys. Detecting neutralisation prevents the silent regression of having the trait on paper but auto-increment integers in practice.

| Property     | Value              |
|--------------|--------------------|
| Rule Name    | `EnforceUlidsRule` |
| Identifier   | `ddd.entities.enforceUlids` |
| Scope        | Class-level (Eloquent models under `\Models\*`) |
| Condition    | The class itself or any ancestor MUST declare `Illuminate\Database\Eloquent\Concerns\HasUlids` in any `TraitUse` statement. The class MUST NOT override `$incrementing = true` nor set `$keyType` to anything other than `'string'`, since both neutralise the trait. |
