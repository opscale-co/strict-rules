# DDD Concept: Repository

> **A Repository mediates between the domain and data mapping layers, offering a collection-like interface to access aggregates.**

---

## 🧠 What It Means

Repositories provide an abstraction layer between your domain logic and the details of data access. Instead of querying or persisting data directly inside entities or services, a repository handles that responsibility.

Repositories should:
- Expose methods that represent domain-relevant queries or actions
- Work with aggregates, not just raw models
- Hide infrastructure concerns from the domain layer

They allow domain logic to remain clean and focused on business behavior, not persistence.

---

## 💡 Considerations

Traditional DDD repositories involve creating an interface and multiple implementations (e.g., MySQL, in-memory, API). Laravel simplifies this through Eloquent and dependency injection.

Because Laravel provides expressive and powerful database tools out of the box, we **don’t strictly follow** the classic repository pattern. Instead, we:

- Group CRUD and query logic into dedicated **Repository Traits**, usually located in `Models\Repositories`
- Implement these traits into Models to isolate persistence
- Allow for future replacement or testing without polluting domain services with Eloquent calls

This strikes a balance between Laravel ergonomics and DDD boundaries.

---

## 🧵 A Data Story Example: OrderRepository

In our influencer merch store, the domain needs to fetch and persist `Order` aggregates. Rather than doing this directly inside services or models, we group this logic into a repository trait:

```php
namespace App\Models\Repositories;

trait OrderRepository {
    public function findByUlid(string $ulid): ?Order {
        return Order::where('ulid', $ulid)->first();
    }

    public function save(Order $order): void {
        $order->save();
    }
}
```

Then, inside the `Order` model:

```php
class Order extends Model {
    use UseUlids;
    use OrderRepository;
}
```

In a service:

```php
$order = $this->order->findByUlid($id);
```

The repository trait encapsulates access logic while keeping models lightweight and testable.

---

## 🚩 Code Smell

> Making Eloquent calls directly in unrelated domain services, helpers, or controllers.

This tightly couples business logic with persistence, making testing and evolution harder.

---

## 🧪 AST Rules

### 📌 `EloquentRestrictionRule`

- **Purpose:** Ensure Eloquent method calls are only made in approved repository or service locations.
- **Description:** Disallows calls to Eloquent model methods within models themselves, except when made from traits in `App\Models\Repositories` or `App\Services`.
- **Justification:** Keeps persistence logic out of domain entities and centralizes it in reusable, testable components.

| Property     | Value                   |
|--------------|--------------------------|
| Rule Name    | `EloquentRestrictionRule`|
| Scope        | Method-level             |
| Condition    | Eloquent calls allowed only in traits under approved namespaces |
