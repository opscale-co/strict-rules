# Implementation Plan: 015-representation-layer-rule

## Summary

Single-line constructor change in `RepresentationLayerRule`: extend `allowedFrameworkImports` with the specific class `Illuminate\\Support\\Carbon`. Update the test from one scenario to the canonical four scenarios with two new fixtures. The trait/Model auto-allow override remains unchanged.

## Phase 1 — Design

### Rule change

```diff
 [ // Allowed framework imports
     'Illuminate\\Database\\',
+    'Illuminate\\Support\\Carbon',
 ],
```

No facade, external or override changes.

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_imports_de_capas_superiores` | `Models/User.php` | 4 errors (existing) |
| 2 | `caso_negativo_modelo_con_traits_y_carbon` | `Models/CarbonTypedModel.php` | 0 errors |
| 3 | `falso_positivo_carbon_typing_no_es_violacion` | same as 2 (focus framing) | 0 errors |
| 4 | `falso_negativo_facade_de_otra_capa_sigue_siendo_violacion` | `Models/StorageUsingModel.php` | 1 error |

### Documentation

`src/Rules/CLEAN/Representation/documentation.md` — refresh with `Illuminate\Support\Carbon` and the package-agnostic note.

## Tasks (see tasks.md)
