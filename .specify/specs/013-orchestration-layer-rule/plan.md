# Implementation Plan: 013-orchestration-layer-rule

## Summary

Two-line constructor change in `OrchestrationLayerRule`: add `Illuminate\\Database\\Eloquent\\` to `allowedFrameworkImports` and `Log` to `allowedFacades`. Update the test from one scenario to the canonical four scenarios with two new fixtures.

## Phase 1 — Design

### Rule change

```diff
 [ // Allowed framework imports
     'Illuminate\\Bus\\',
     'Illuminate\\Contracts\\Queue\\',
+    'Illuminate\\Database\\Eloquent\\',
     'Illuminate\\Foundation\\Bus\\',
     'Illuminate\\Mail\\',
     'Illuminate\\Notifications\\',
     'Illuminate\\Queue\\',
 ],
 [ // Allowed facades
     'Bus',
     'Concurrency',
+    'Log',
     'Mail',
     'Notification',
     'Pipeline',
     'Queue',
     'Redis',
     'Schedule',
 ],
 [] // Allowed external imports
```

### Tests

| # | Method | Fixture | Expected |
|---|---|---|---|
| 1 | `caso_positivo_dependencia_capa_superior_y_facade_no_permitida` | `Jobs/CleanOldProducts.php` | 2 errors |
| 2 | `caso_negativo_job_con_log_y_eloquent_typing` | `Jobs/ValidJob.php` | 0 errors |
| 3 | `falso_positivo_eloquent_model_typing_no_es_violacion` | same as 2 (focus framing) | 0 errors |
| 4 | `falso_negativo_facade_de_otra_capa_sigue_siendo_violacion` | `Jobs/DbAccessJob.php` | 1 error |

### Documentation

`src/Rules/CLEAN/Orchestration/documentation.md` — refresh allowed-imports list with `Illuminate\Database\Eloquent\` and `Log`, plus the package-agnostic note.

## Tasks (see tasks.md)
