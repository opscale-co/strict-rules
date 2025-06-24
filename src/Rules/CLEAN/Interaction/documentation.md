# Clean Architecture Layer: Interaction

> **Interfaces with external systems or users—via HTTP, CLI, Nova, or UI.**

---

## 🧠 What It Means

The **Interaction layer** is the entry point of your application. It receives user input or external triggers and forwards the request to the domain. This layer is concerned with handling requests—not making decisions.

Includes:
- Controllers (HTTP, Nova)
- Console commands
- Policies
- Input validation
- UI layer bindings

No business logic should live here. It should **delegate** to Actions or Services.

---

## 💡 Considerations in Laravel

Laravel provides rich tools for interaction:

- `App\Http\Controllers\` — Web and API controllers
- `App\Console\Commands\` — CLI interfaces
- `App\Nova\` — UI for internal operations
- `App\Policies\` — Access control

Use this layer only to validate, authorize, and route requests.

---

## 🧵 A Data Story Example: Checkout Controller

```php
class CheckoutController {
    public function __invoke(Request $request): JsonResponse {
        $order = Order::findOrFail($request->input('order_id'));

        $result = Checkout::run($order); // Delegated to Laravel Action

        return response()->json(['status' => 'ok', 'result' => $result]);
    }
}
```

Here, the controller simply captures input and forwards the flow to the `Checkout` Action. No business rule is applied here.

---

## 🚀 Allowed Imports

| Type      | Allowed Namespaces                                                                                   |
|-----------|--------------------------------------------------------------------------------------------------------|
| Project   | `App\Http\`, `App\Console\`, `App\Nova\`, `App\Policies\`, `App\Models\`, `App\Services\` |
| Framework | `Illuminate\Routing`, `Illuminate\Console`, `Symfony\Component\Console`, `Illuminate\Http`, `Laravel\Nova`, `Laravel\Sanctum`, `Laravel\Passport`, `Livewire`, `Inertia` |
| Facades   | `Artisan`, `Auth`, `Blade`, `Context`, `Cookie`, `Gate`, `Lang`, `Password`, `Process`, `RateLimiter`, `Redirect`, `Request`, `Response`, `Route`, `Session`, `URL`, `Validator`, `View`, `Vite` |

This layer may use any lower layers (including orchestration and transformation) but **must not implement logic** directly.

---

## 🚩 Code Smells

- Business logic in controllers
- Multiple service calls chained in a controller
- Rendering views from inside services

---

## 🧪 AST Rules

### 📌 AST Rule: `InteractionLayerRule`

- **Purpose:** Enforce allowed imports for the Interaction layer
- **Description:** Limits usage to user-facing components, framework request/response handling, and UI elements
- **Justification:** Prevents logic bloat and preserves separation between interaction and transformation

| Property     | Value                      |
|--------------|----------------------------|
| Rule Name    | `InteractionLayerRule`     |
| Scope        | File-level                 |
| Condition    | Allow only specific class and facade imports based on Clean Architecture assumptions |
