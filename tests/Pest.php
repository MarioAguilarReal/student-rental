<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Closure-based (Pest) feature tests run against PostgreSQL (see phpunit.xml)
| with a fresh database per test. Class-based tests shipped with the starter
| kit configure themselves.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Unit tests boot the framework (translator, config) but never touch the database.
pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Business modules
|--------------------------------------------------------------------------
*/

/**
 * @return list<string>
 */
function businessModules(): array
{
    return [
        'Identity',
        'Verification',
        'Catalog',
        'Properties',
        'Pricing',
        'Rentals',
        'Payments',
        'Inspections',
        'Notifications',
        'Audit',
    ];
}
