<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Configuration;
use App\Models\OptionGroup;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConfigurationApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_configuration_via_api()
    {
        $response = $this->postJson('/api/configurations');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'config_id'
                 ])
                 ->assertJson(['success' => true]);
        
        $this->assertDatabaseCount('configurations', 1);
    }

    /** @test */
    public function it_can_get_configuration_via_api()
    {
        $config = Configuration::factory()->create();

        $response = $this->getJson("/api/configurations/{$config->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $config->id
                     ]
                 ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_configuration()
    {
        $response = $this->getJson('/api/configurations/99999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'error' => 'Configuration not found'
                 ]);
    }

    /** @test */
    public function it_can_select_option_via_api()
    {
        $config = Configuration::factory()->create();
        $option = Option::factory()->create(['price_value' => 99.99]);

        $response = $this->postJson("/api/configurations/{$config->id}/select", [
            'option_id' => $option->id,
            'quantity' => 1
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'total' => 99.99
                 ]);
    }

    /** @test */
    public function it_validates_option_id_on_select()
    {
        $config = Configuration::factory()->create();

        $response = $this->postJson("/api/configurations/{$config->id}/select", [
            'quantity' => 1
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['option_id']);
    }

    /** @test */
    public function it_validates_quantity_on_select()
    {
        $config = Configuration::factory()->create();
        $option = Option::factory()->create();

        $response = $this->postJson("/api/configurations/{$config->id}/select", [
            'option_id' => $option->id,
            'quantity' => 0
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_remove_option_via_api()
    {
        $config = Configuration::factory()->create();
        $group = OptionGroup::factory()->create();
        $option = Option::factory()->create(['option_group_id' => $group->id]);

        $this->postJson("/api/configurations/{$config->id}/select", [
            'option_id' => $option->id,
            'quantity' => 1
        ]);

        $response = $this->deleteJson("/api/configurations/{$config->id}/select/{$group->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        $this->assertDatabaseCount('configuration_items', 0);
    }

    /** @test */
    public function it_can_clear_configuration_via_api()
    {
        $config = Configuration::factory()->create();
        $option = Option::factory()->create();

        $this->postJson("/api/configurations/{$config->id}/select", [
            'option_id' => $option->id,
            'quantity' => 1
        ]);

        $response = $this->putJson("/api/configurations/{$config->id}/clear");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'total' => 0
                 ]);
        
        $this->assertDatabaseCount('configuration_items', 0);
    }

    /** @test */
    public function it_can_get_configuration_summary_via_api()
    {
        $config = Configuration::factory()->create();
        
        $group = OptionGroup::factory()->create();
        $option = Option::factory()->create([
            'option_group_id' => $group->id,
            'price_value' => 99.99
        ]);

        $this->postJson("/api/configurations/{$config->id}/select", [
            'option_id' => $option->id,
            'quantity' => 1
        ]);

        $response = $this->getJson("/api/configurations/{$config->id}/summary");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'total_items' => 1,
                         'total_price' => 99.99
                     ]
                 ]);
    }
}