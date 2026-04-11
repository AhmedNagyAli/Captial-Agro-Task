<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\OptionGroup;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_all_groups()
    {
        OptionGroup::factory()->count(3)->create();

        $response = $this->getJson('/api/catalog/groups');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_all_options()
    {
        $group = OptionGroup::factory()->create();
        Option::factory()->count(5)->create(['option_group_id' => $group->id]);

        $response = $this->getJson('/api/catalog/options');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_get_group_options()
    {
        $group = OptionGroup::factory()->create();
        Option::factory()->count(3)->create(['option_group_id' => $group->id]);

        $response = $this->getJson("/api/catalog/groups/{$group->id}/options");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_group()
    {
        $response = $this->getJson('/api/catalog/groups/99999/options');

        $response->assertStatus(404);
    }
}