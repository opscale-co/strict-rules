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

- **Purpose:** Ensure Eloquent CRUD calls happen only inside Repository traits or Services.
- **Description:** Flags any Eloquent CRUD call — query building (`where`, `orderBy`, `select`, `join`, ...), retrieval (`find`, `first`, `get`, `paginate`, ...), aggregates (`count`, `sum`, `exists`, ...) or persistence (`create`, `update`, `save`, `delete`, ...) — made by a class whose namespace is **not** under `\Models\Repositories\*` or `\Services\*`. The rule covers Models themselves, Controllers, Jobs, Listeners, Observers, Nova classes, Console commands and any other location. Relationship declarations (`belongsTo`, `hasMany`, `morphTo`, `with`, `load`, ...), collection iteration helpers (`chunk`, `cursor`, ...), model state accessors (`getAttribute`, `fill`, ...), timestamp utilities, event hooks and serialization helpers (`toArray`, `toJson`, ...) are intentionally **not** flagged — they are legitimate Model concerns. Static calls to `self`/`static`/`parent` and `$this->method()` calls are only flagged when the enclosing class is itself an Eloquent Model — non-Eloquent helpers that happen to declare same-named methods (`find`, `get`) are not mis-flagged.
- **Justification:** Keeps persistence logic out of domain entities, HTTP controllers, queued jobs and any orchestration glue, centralising it in dedicated repositories and services — without overreaching into the Model's own responsibilities (relationships, state, serialization).

| Property     | Value                   |
|--------------|--------------------------|
| Rule Name    | `EloquentRestrictionRule`|
| Identifier   | `ddd.repositories.eloquentRestriction` |
| Scope        | Class-level. Applies to every class whose namespace is **not** under `\Models\Repositories\*` or `\Services\*`. Enums are skipped. |
| Condition    | A `StaticCall` whose target class is an Eloquent Model FQCN with a CRUD method is always flagged. A `StaticCall` to `self`/`static`/`parent`, or a `MethodCall` on `$this`, is flagged only when the enclosing class is itself an Eloquent Model. The curated method list covers query building, retrieval, pagination, aggregates and persistence; relationships, collection iteration, model state, timestamps, events and serialization helpers are excluded. |
