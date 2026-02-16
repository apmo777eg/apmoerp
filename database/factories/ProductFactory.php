<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->lexify('SKU-????-####'),
            'barcode' => fake()->unique()->ean13(),
            'default_price' => fake()->randomFloat(2, 10, 1000),
            'standard_cost' => fake()->randomFloat(2, 5, 500),
            'cost' => fake()->randomFloat(2, 5, 500),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'min_stock' => fake()->numberBetween(5, 20),
            'max_stock' => fake()->numberBetween(50, 200),
            'reorder_point' => fake()->numberBetween(10, 30),
            'lead_time_days' => fake()->numberBetween(1, 14),
            'location_code' => fake()->optional()->regexify('[A-Z]{2}-[0-9]{2}-[0-9]{2}'),
            'status' => 'active',
            'type' => 'stock',
            'branch_id' => Branch::factory(),
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            // products table uses stock_quantity (not quantity)
            'stock_quantity' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            // products table uses stock_quantity (not quantity)
            'stock_quantity' => fake()->numberBetween(1, 4),
            'min_stock' => 5,
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            // Services are not stock-tracked in the traditional sense.
            // Keep stock_quantity at 0 but mark product_type as service.
            'product_type' => 'service',
            'type' => 'service',
            'stock_quantity' => 0,
            'min_stock' => 0,
            'track_stock_alerts' => false,
        ]);
    }
}
