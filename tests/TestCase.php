<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function owner(): User
    {
        return User::factory()->owner()->create();
    }

    protected function manager(): User
    {
        return User::factory()->manager()->create();
    }

    protected function worker(): User
    {
        return User::factory()->worker()->create();
    }

    protected function veterinarian(): User
    {
        return User::factory()->veterinarian()->create();
    }

    protected function financeUser(): User
    {
        return User::factory()->finance()->create();
    }
}
