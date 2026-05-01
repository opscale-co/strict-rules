<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest bootstrap — strict-rules library
|--------------------------------------------------------------------------
| This library is a PHPStan extension. Each rule is exercised through
| PHPStan's RuleTestCase, which is a class-based PHPUnit test. Pest is
| backward-compatible with PHPUnit, so the existing class-based tests
| under tests/Rules/ run as-is. No `uses()` binding is required because
| each test class extends PHPStan\Testing\RuleTestCase directly.
|
| Library-type adaptations vs. the standard opscale-test skill:
|   - Single test suite (Rules), no Unit/Feature/Browser split
|   - No Orchestra Testbench, no Nova DevTool, no Dusk
|   - No tests/TestCase.php — RuleTestCase is the base
*/
