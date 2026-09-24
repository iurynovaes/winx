<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Camisetas', 'Calçados', 'Acessórios'] as $nome) {
            Category::factory()->create([
                'nome' => $nome,
            ]);
        }
    }
}
