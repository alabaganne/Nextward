<?php

namespace Tests\Feature;

use App\Models\City;
use Tests\Fixtures\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeding_refuses_production_before_changing_records(): void
    {
        $city = City::create(['name' => 'Existing city']);
        $this->app->detectEnvironment(fn () => 'production');

        try {
            $this->app->make(DemoSeeder::class)->run();
            $this->fail('Demo seeding should refuse production.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Demo data can only be seeded in local or testing environments.', $exception->getMessage());
        }

        $this->assertDatabaseHas('cities', ['id' => $city->id, 'name' => 'Existing city']);
    }
}
