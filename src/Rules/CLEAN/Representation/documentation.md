# Clean Architecture Layer: Representation

> **Defines what things are—not what they do.**

---

## 🧠 What It Means

The **Representation layer** is the foundation of the system's domain model. It includes the data structures that define business entities and their attributes. This layer should be free from behavior that involves orchestration, communication, or transformation logic.

Its responsibilities include:
- Describing entities and their fields (Eloquent Models)
- Representing fixed values (Enums)
- Encapsulating atomic business rules (Value Objects)
- Grouping low-level persistence operations (Repositories)

This layer establishes what the system knows, not what the system does.

---

## 💡 Considerations

In Laravel, this layer typically includes:

- `App\Models\` — Eloquent models
- `App\Models\Enums\` — Application-specific enum types
- `App\Models\ValueObjects\` — Value objects casted with Laravel custom casts
- `App\Models\Repositories\` — Traits containing repository logic for model-specific queries

Laravel models are inherently tied to persistence, but in our context, we restrict their use to structural logic (fields, relationships, type casting, validation helpers). Any process, event triggering, or external call is a violation of this layer.

---

## 🧵 A Data Story Example: Products and Influencers

```php
class Product extends Model {
    use HasUlids;

    protected $fillable = ['title', 'price'];

    protected $casts = [
        'price' => Price::class,
    ];

    public function influencer() {
        return $this->belongsTo(Influencer::class);
    }
}

class Influencer extends Model {
    use HasUlids;

    protected $fillable = ['name', 'email'];

    protected $casts = [
        'email' => Email::class,
    ];

    public function products() {
        return $this->hasMany(Product::class);
    }
}
```

Here, both models define relationships, fields, and custom value objects. There's no logic around pricing strategy, stock availability, or notifications—those belong elsewhere.

---

## 🚀 Allowed Imports

| Type      | Allowed Namespaces                                      |
|-----------|----------------------------------------------------------|
| Project   | Any class under `\Models\` (including `\Models\ValueObjects`, `\Models\Enums`, `\Models\Repositories` traits). The root namespace is package-agnostic — `Opscale\Models\Foo`, `App\Models\Foo`, and `Vendor\Package\Models\Foo` all detect as layer 1. |
| Framework | `Illuminate\Database\` (entire Eloquent / migration / query-builder tree), `Illuminate\Support\Carbon` (date typing only — no other Support helpers, no Support facades). |
| External  | `Carbon\` (the carbon package), `Spatie\` |
| Facades   | `DB`, `Hash`, `Schema`                                  |
| Override  | When the file's class is an Eloquent Model, ANY trait import or Eloquent-Model class import is auto-allowed regardless of namespace. This covers `Authenticatable`, `Notifiable`, `HasFactory`, `HasUlids`, package traits, etc. |

Imports from Communication, Transformation, Orchestration, or Interaction layers are **not allowed**.

---

## 🚩 Code Smells

- Triggering events or notifications from a Model
- Calling services or jobs within Model methods
- Adding transformation logic in attribute accessors or mutators

---

## 🧪 AST Rules

### 📌 AST Rule: `RepresentationLayerRule`

- **Purpose:** Enforce allowed imports for the Representation layer
- **Description:** Disallows usage of classes from higher layers and restricts Facade usage to `DB`, `Hash`, and `Schema`
- **Justification:** Keeps the layer pure and focused on representing data structures

| Property     | Value                      |
|--------------|----------------------------|
| Rule Name    | `RepresentationLayerRule`  |
| Scope        | File-level                 |
| Condition    | Allow only specific class and facade imports based on Clean Architecture assumptions |
