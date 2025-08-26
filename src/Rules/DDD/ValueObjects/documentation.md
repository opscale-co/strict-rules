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

## 🧵 A Data Story Example: US Coordinate Validation as a Value Object

In our location-based application, we store geographic coordinates for venues and events. There is a **specific business rule**: *coordinates must be within the continental United States boundaries*.

To enforce this consistently, we encapsulate the validation in a value object:

```php
namespace App\Models\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class USCoordinate implements CastsAttributes
{
    // Continental US approximate boundaries
    private const MIN_LATITUDE = 24.396308;   // Southern tip of Florida Keys
    private const MAX_LATITUDE = 49.384358;   // Northern border with Canada
    private const MIN_LONGITUDE = -125.0;     // West coast (Washington/Oregon)
    private const MAX_LONGITUDE = -66.93457;  // East coast (Maine)

    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude
    ) {
        $this->validateCoordinates($latitude, $longitude);
    }

    public function get(Model $model, string $key, $value, array $attributes): ?self
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);
        
        return new self(
            $data['latitude'] ?? 0.0,
            $data['longitude'] ?? 0.0
        );
    }

    public function set(Model $model, string $key, $value, array $attributes): string
    {
        if ($value === null) {
            return json_encode(null);
        }

        if ($value instanceof self) {
            return json_encode([
                'latitude' => $value->latitude,
                'longitude' => $value->longitude,
            ]);
        }

        if (is_array($value)) {
            $lat = $value['latitude'] ?? $value['lat'] ?? 0.0;
            $lng = $value['longitude'] ?? $value['lng'] ?? 0.0;
            
            $coordinate = new self($lat, $lng);
            return json_encode([
                'latitude' => $coordinate->latitude,
                'longitude' => $coordinate->longitude,
            ]);
        }

        throw new InvalidArgumentException('Invalid coordinate format provided.');
    }

    private function validateCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < self::MIN_LATITUDE || $latitude > self::MAX_LATITUDE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Latitude %.6f is outside US boundaries (%.6f to %.6f)',
                    $latitude,
                    self::MIN_LATITUDE,
                    self::MAX_LATITUDE
                )
            );
        }

        if ($longitude < self::MIN_LONGITUDE || $longitude > self::MAX_LONGITUDE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Longitude %.6f is outside US boundaries (%.6f to %.6f)',
                    $longitude,
                    self::MIN_LONGITUDE,
                    self::MAX_LONGITUDE
                )
            );
        }
    }

    public function getFormattedCoordinate(): string
    {
        return sprintf('%.6f, %.6f', $this->latitude, $this->longitude);
    }

    public function isWithinRadius(USCoordinate $other, float $radiusMiles): bool
    {
        $earthRadius = 3959; // Earth's radius in miles
        
        $latDelta = deg2rad($other->latitude - $this->latitude);
        $lngDelta = deg2rad($other->longitude - $this->longitude);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($other->latitude)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        
        return $distance <= $radiusMiles;
    }
}
```

In the model:

```php
class Venue extends Model {
    protected $casts = [
        'location' => USCoordinate::class,
    ];
    
    protected $fillable = [
        'name',
        'location',
        'address',
    ];
}
```

Usage examples:

```php
// Valid US coordinates
$venue = new Venue([
    'name' => 'Central Park',
    'location' => ['latitude' => 40.785091, 'longitude' => -73.968285]
]);

// Invalid coordinates (outside US) will throw exception
try {
    $venue = new Venue([
        'name' => 'Invalid Location',
        'location' => ['latitude' => 51.5074, 'longitude' => -0.1278] // London, UK
    ]);
} catch (InvalidArgumentException $e) {
    // Handles coordinates outside US boundaries
}

// Business logic methods available
$distance = $venue->location->isWithinRadius($otherVenue->location, 50); // within 50 miles?
$formatted = $venue->location->getFormattedCoordinate(); // "40.785091, -73.968285"
```

Now, any time we store coordinates, the US boundary validation is automatically enforced, and we have domain-specific methods available for geographic calculations.

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
