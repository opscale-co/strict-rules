# Implementation Plan: 014-transformation-layer-rule

## Summary

Constructor change in `TransformationLayerRule`: extend `allowedFrameworkImports` with the specific Support helper classes used in Services (`Arr`, `Collection`, `Number`, `Str`) and add `Log` to `allowedFacades`. Avoid the broader `Illuminate\Support\` prefix — it would otherwise allow `Illuminate\Support\Facades\X` to bypass the facade gate, breaking the regression guard.

## Phase 1 — Design

### Rule change

```diff
 [ // Allowed framework imports
     'Illuminate\\Contracts\\',
     'Illuminate\\Foundation\\',
+    'Illuminate\\Http\\Client\\',
+    'Illuminate\\Support\\Arr',
+    'Illuminate\\Support\\Collection',
+    'Illuminate\\Support\\Number',
+    'Illuminate\\Support\\Str',
     'Symfony\\Component\\',
-    'Illuminate\\Http\\Client\\',
 ],
 [ // Allowed facades
     'App',
     'Cache',
     'Config',
     'Crypt',
     'Exceptions',
     'File',
     'Http',
+    'Log',
     'Storage',
 ],
```

### Why class-specific framework prefixes

`isAllowedUse` runs `isAllowedFacade` first and returns immediately on a match. BUT when `isAllowedFacade` returns `false` (because the short facade name is not in `allowedFacades`), execution falls through to `isAllowedFrameworkUse`. If we put the broad prefix `Illuminate\Support\` there, a `use Illuminate\Support\Facades\DB;` would:

1. Pass `isAllowedFacade`: TRUE? No — `'DB'` is not in Transformation's allowedFacades. Returns false.
2. Pass `isAllowedFrameworkUse`: `str_starts_with('Illuminate\Support\Facades\DB', 'Illuminate\Support\')` → TRUE. Returns true.

The DB facade would be accidentally allowed, breaking the falso_negativo regression guard.

Listing class-specific prefixes (`Illuminate\Support\Arr`, ...) ensures `str_starts_with('Illuminate\Support\Facades\DB', 'Illuminate\Support\Arr')` is FALSE.

Note that `'Illuminate\\Support\\Str'` will still match both `Illuminate\Support\Str` and `Illuminate\Support\Stringable` via str_starts_with — that's a small bonus, not a leak.

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_facade_y_dependencia_capa_superior` | `Services/ExternalAPIService.php` | 2 errors |
| 2 | `caso_negativo_service_con_log_y_support_helpers` | `Services/ServiceUsingHelpers.php` | 0 errors |
| 3 | `falso_positivo_support_helpers_no_son_violaciones` | same as 2 (focus framing) | 0 errors |
| 4 | `falso_negativo_facade_de_otra_capa_sigue_siendo_violacion` | `Services/DbAccessService.php` | 1 error |

### Documentation

`src/Rules/CLEAN/Transformation/documentation.md` — refresh with the new helper classes and `Log` facade.

## Tasks (see tasks.md)
