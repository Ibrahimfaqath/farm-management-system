<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Farm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('animals.index'))->assertRedirect(route('login'));
    }

    public function test_worker_cannot_manage_animals(): void
    {
        $this->actingAs($this->worker())
            ->get(route('animals.index'))
            ->assertOk();

        $this->actingAs($this->worker())
            ->get(route('animals.create'))
            ->assertForbidden();
    }

    public function test_veterinarian_cannot_manage_animals(): void
    {
        $this->actingAs($this->veterinarian())
            ->get(route('animals.create'))
            ->assertForbidden();
    }

    public function test_finance_user_cannot_open_animal_module(): void
    {
        $this->actingAs($this->financeUser())
            ->get(route('animals.index'))
            ->assertForbidden();

        $this->actingAs($this->financeUser())
            ->get(route('animals.create'))
            ->assertForbidden();
    }

    public function test_owner_can_open_animal_management(): void
    {
        $animal = Animal::factory()->create();

        $this->actingAs($this->owner())
            ->get(route('animals.show', $animal))
            ->assertOk()
            ->assertSee($animal->animal_code);
    }

    public function test_sold_animals_remain_viewable(): void
    {
        $animal = Animal::factory()->sold()->create();

        $this->actingAs($this->owner())
            ->get(route('animals.show', $animal))
            ->assertOk()
            ->assertSee('Terjual');
    }

    public function test_farm_management_requires_owner_role(): void
    {
        $farm = Farm::factory()->create();

        $this->actingAs($this->manager())->get(route('farms.create'))->assertForbidden();
        $this->actingAs($this->owner())->get(route('farms.create'))->assertOk();
    }
}
