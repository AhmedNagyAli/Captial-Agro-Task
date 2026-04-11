<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Configuration;
use App\Models\OptionGroup;
use App\Models\Option;
use App\Models\ConfigurationItem;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConfigurationService();
    }

    #[Test]
    public function it_can_create_a_new_configuration()
    {
        $config = $this->service->createConfiguration();

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertNotNull($config->session_id);
        $this->assertEquals(0, $config->total_price);
        $this->assertDatabaseHas('configurations', ['id' => $config->id]);
    }

    #[Test]
    public function it_can_select_an_option()
    {
        $config = Configuration::factory()->create();
        $group = OptionGroup::factory()->create();
        $option = Option::factory()->create([
            'option_group_id' => $group->id,
            'price_value' => 99.99
        ]);

        // Pass the Option object, not just the ID
        $result = $this->service->selectOption($config, $option, 1);

        $this->assertEquals(99.99, $result->total_price);
        $this->assertCount(1, $result->items);

        $this->assertDatabaseHas('configuration_items', [
            'configuration_id' => $config->id,
            'option_group_id' => $group->id,
            'option_id' => $option->id,
            'price' => 99.99
        ]);
    }

    #[Test]
    public function it_replaces_existing_option_in_same_group()
    {
        $config = Configuration::factory()->create();
        $group = OptionGroup::factory()->create();
        $option1 = Option::factory()->create([
            'option_group_id' => $group->id,
            'price_value' => 50.00
        ]);
        $option2 = Option::factory()->create([
            'option_group_id' => $group->id,
            'price_value' => 75.00
        ]);

        $this->service->selectOption($config, $option1, 1);
        $this->service->selectOption($config, $option2, 1);

        $this->assertCount(1, $config->fresh()->items);
        $this->assertEquals(75.00, $config->fresh()->total_price);
    }

    #[Test]
    public function it_can_clear_all_options()
    {
        $config = Configuration::factory()->create();
        $groups = OptionGroup::factory()->count(3)->create();

        foreach ($groups as $group) {
            $option = Option::factory()->create([
                'option_group_id' => $group->id,
                'price_value' => 25.00
            ]);
            $this->service->selectOption($config, $option, 1);
        }

        $this->assertCount(3, $config->fresh()->items);

        $result = $this->service->clearConfiguration($config);

        $this->assertEquals(0, $result->total_price);
        $this->assertCount(0, $result->items);
        $this->assertDatabaseCount('configuration_items', 0);
    }

    #[Test]
    public function it_calculates_total_price_correctly()
    {
        $config = Configuration::factory()->create();

        $group1 = OptionGroup::factory()->create();
        $option1 = Option::factory()->create([
            'option_group_id' => $group1->id,
            'price_value' => 299.99
        ]);

        $group2 = OptionGroup::factory()->create();
        $option2 = Option::factory()->create([
            'option_group_id' => $group2->id,
            'price_value' => 499.99
        ]);

        $group3 = OptionGroup::factory()->create();
        $option3 = Option::factory()->create([
            'option_group_id' => $group3->id,
            'price_value' => 149.99
        ]);

        $this->service->selectOption($config, $option1, 1);
        $this->service->selectOption($config, $option2, 1);
        $this->service->selectOption($config, $option3, 1);

        $expectedTotal = 299.99 + 499.99 + 149.99;
        $this->assertEquals($expectedTotal, $config->fresh()->total_price);
    }

    #[Test]
    public function it_saves_snapshot_data_correctly()
    {
        $config = Configuration::factory()->create();
        $group = OptionGroup::factory()->create(['name' => 'Test Group']);
        $option = Option::factory()->create([
            'option_group_id' => $group->id,
            'name' => 'Test Option',
            'price_value' => 99.99
        ]);

        $this->service->selectOption($config, $option, 1);

        $item = ConfigurationItem::where('configuration_id', $config->id)->first();

        $this->assertEquals('Test Group', $item->option_group_name);
        $this->assertEquals('Test Option', $item->option_name);
        $this->assertEquals(99.99, $item->price);
    }
}