<?php

namespace Database\Factories;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'nome' => fake()->words(3, true),
            'descricao' => fake()->sentence(),
            'preco' => fake()->randomFloat(2, 1, 500),
            'category_id' => Category::factory(),
            'estoque' => fake()->numberBetween(0, 100),
            'status' => ProductStatus::Ativo,
        ];
    }

    /**
     * Indicate that the product is hidden from sale.
     */
    public function inativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Inativo,
        ]);
    }
}
