<?php

namespace Tests\Feature;

use App\Enums\AnimalSpecies;
use App\Models\Breed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DairyCattleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_and_animal_form_only_offer_active_dairy_cattle(): void
    {
        $active = Breed::factory()->create(['name' => 'Jersey', 'is_active' => true]);
        $retired = Breed::factory()->create(['name' => 'Limosin', 'is_active' => false]);

        $user = $this->owner();

        $breeds = $this->actingAs($user)->get(route('breeds.index'))->assertOk()->getContent();
        $form = $this->actingAs($user)->get(route('animals.create'))->assertOk()->getContent();

        $this->assertStringContainsString($active->name, $breeds);
        $this->assertStringNotContainsString($retired->name, $breeds);
        $this->assertStringContainsString($active->name, $form);
        $this->assertStringNotContainsString($retired->name, $form);
    }

    public function test_the_only_selectable_species_is_cattle(): void
    {
        $this->assertSame(['cattle'], AnimalSpecies::values());

        $html = $this->actingAs($this->owner())
            ->get(route('breeds.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('name="species"', $html);
    }
}
