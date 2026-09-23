<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against a fresh in-memory SQLite database with roles and
| permissions seeded (see phpunit.xml and Tests\TestCase).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seedRoles())
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
