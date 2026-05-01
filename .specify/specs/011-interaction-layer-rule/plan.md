# Implementation Plan: 011-interaction-layer-rule

## Summary

Two-line constructor change in `InteractionLayerRule`: extend `allowedExternalImports` to permit MCP, Nova, Inertia and Sanctum, and add `Log` to `allowedFacades`. Update the existing test from one scenario to the canonical four scenarios and add two new fixtures.

## Phase 1 — Design

### Rule change

```diff
 [ // Allowed framework imports
     'Illuminate\\Console\\',
     'Illuminate\\Http\\',
     'Illuminate\\Routing\\',
     'Illuminate\\Foundation\\',
     'Illuminate\\Validation\\',
     'Symfony\\Component\\HttpFoundation\\',
 ],
 [ // Allowed facades
     'Artisan',
     'Auth',
     'Blade',
     'Context',
     'Cookie',
     'Gate',
     'Lang',
+    'Log',
     'Password',
     'Process',
     'RateLimiter',
     'Redirect',
     'Request',
     'Response',
     'Route',
     'Session',
     'URL',
     'Validator',
     'View',
     'Vite',
 ],
-[] // Allowed external imports
+[ // Allowed external imports
+    'Inertia\\',
+    'Laravel\\Nova\\',
+    'Laravel\\Sanctum\\',
+    'Mcp\\',
+    'PhpMcp\\',
+]
```

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_facade_de_capa_inferior_es_violacion` | `Http/Controllers/ProductController.php` | 1 error (DB facade) |
| 2 | `caso_negativo_imports_permitidos_incluyendo_mcp_nova_inertia_sanctum` | `Http/Controllers/McpInertiaController.php` | 0 |
| 3 | `falso_positivo_mcp_nova_inertia_sanctum_no_son_violaciones` | `Http/Controllers/McpInertiaController.php` (subset focus) | 0 |
| 4 | `falso_negativo_facade_de_otra_capa_sigue_siendo_violacion` | `Http/Controllers/DbAccessController.php` | 1 error |

Tests 2 and 3 share the same fixture — different framing (overall happy path vs. specifically MCP-only). Test 4 is a regression guard.

### Documentation

`src/Rules/CLEAN/Interaction/documentation.md` — refresh the Interaction layer block listing the new allowed externals and Log facade.

## Tasks (see tasks.md)
