# Implementation Plan: 012-communication-layer-rule

## Summary

Two-line constructor change in `CommunicationLayerRule`: extend `allowedFrameworkImports` with `Illuminate\\Database\\Eloquent\\` (Observer type hints) and add `Log` to `allowedFacades`. Update the existing test from one scenario to the canonical four scenarios with two new fixtures.

## Phase 1 — Design

### Rule change

```diff
 [ // Allowed framework imports
+    'Illuminate\\Database\\Eloquent\\',
     'Illuminate\\Contracts\\Broadcasting\\',
     'Illuminate\\Events\\',
 ],
 [ // Allowed facades
     'Broadcast',
     'Event',
+    'Log',
 ],
 [] // Allowed external imports
```

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_facade_y_dependencia_capa_superior` | `Observers/ProductObserver.php` | 2 errors |
| 2 | `caso_negativo_observer_con_log_event_broadcast_y_eloquent_typing` | `Observers/ValidObserver.php` | 0 errors |
| 3 | `falso_positivo_eloquent_model_typing_no_es_violacion` | same as 2 (focus on framework typing) | 0 errors |
| 4 | `falso_negativo_facade_de_otra_capa_sigue_siendo_violacion` | `Observers/DbAccessObserver.php` | 1 error |

### Documentation

`src/Rules/CLEAN/Communication/documentation.md` — refresh allowed-imports list with `Illuminate\Database\Eloquent\` and `Log`.

## Tasks (see tasks.md)
