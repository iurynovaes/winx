<?php

namespace Database\Seeders;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $camisetas = Category::query()->where('nome', 'Camisetas')->firstOrFail();

        Product::factory()->create([
            'sku' => 'CAM-001',
            'nome' => 'Camiseta Básica',
            'descricao' => 'Camiseta de algodão.',
            'preco' => '49.90',
            'category_id' => $camisetas->id,
            'estoque' => 20,
            'status' => ProductStatus::Ativo,
        ]);

        Product::factory()
            ->count(9)
            ->recycle(Category::query()->get())
            ->create();
    }
}
