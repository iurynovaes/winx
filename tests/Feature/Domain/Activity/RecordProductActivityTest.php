<?php

namespace Tests\Feature\Domain\Activity;

use App\Domain\Activity\Enums\ProductActivityAction;
use App\Jobs\RecordProductActivity;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecordProductActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_product_queues_the_activity_log(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)->postJson(route('v1.products.store'), [
            'nome' => 'Camiseta Básica',
            'preco' => '49.90',
            'categoria_id' => $category->id,
            'sku' => 'CAM-001',
            'estoque' => 20,
        ]);

        Queue::assertPushed(RecordProductActivity::class, function (RecordProductActivity $job) use ($user): bool {
            return $job->action === ProductActivityAction::Criado
                && $job->userId === $user->id
                && $job->before === null
                && $job->after['nome'] === 'Camiseta Básica'
                && $job->after['sku'] === 'CAM-001';
        });
    }

    public function test_creating_a_product_persists_the_activity_log(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)->postJson(route('v1.products.store'), [
            'nome' => 'Camiseta Básica',
            'preco' => '49.90',
            'categoria_id' => $category->id,
            'sku' => 'CAM-001',
            'estoque' => 20,
        ]);

        $activity = ProductActivity::query()->first();

        $this->assertNotNull($activity);
        $this->assertSame($user->id, $activity->user_id);
        $this->assertSame(ProductActivityAction::Criado, $activity->action);
        $this->assertNull($activity->before);
        $this->assertSame('Camiseta Básica', $activity->after['nome']);
        $this->assertSame('49.90', $activity->after['preco']);
    }

    public function test_updating_a_product_persists_the_previous_and_new_state(): void
    {
        $product = Product::factory()->create([
            'nome' => 'Camiseta',
            'estoque' => 20,
        ]);

        $this->withToken($this->token())->patchJson(route('v1.products.update', $product), [
            'estoque' => 5,
        ]);

        $activity = ProductActivity::query()->first();

        $this->assertNotNull($activity);
        $this->assertSame(ProductActivityAction::Atualizado, $activity->action);
        $this->assertSame(20, $activity->before['estoque']);
        $this->assertSame(5, $activity->after['estoque']);
        $this->assertSame('Camiseta', $activity->after['nome']);
    }

    public function test_updating_without_changes_does_not_record_activity(): void
    {
        $product = Product::factory()->create([
            'nome' => 'Camiseta',
        ]);

        $this->withToken($this->token())->patchJson(route('v1.products.update', $product), [
            'nome' => 'Camiseta',
        ]);

        $this->assertDatabaseCount('product_activities', 0);
    }

    public function test_deleting_a_product_persists_the_previous_state(): void
    {
        $product = Product::factory()->create([
            'nome' => 'Camiseta',
        ]);

        $this->withToken($this->token())->deleteJson(route('v1.products.destroy', $product));

        $activity = ProductActivity::query()->first();

        $this->assertNotNull($activity);
        $this->assertSame($product->id, $activity->product_id);
        $this->assertSame(ProductActivityAction::Excluido, $activity->action);
        $this->assertSame('Camiseta', $activity->before['nome']);
        $this->assertNull($activity->after);
        $this->assertModelMissing($product);
    }

    public function test_unauthenticated_request_does_not_record_activity(): void
    {
        $this->postJson(route('v1.products.store'), []);

        $this->assertDatabaseCount('product_activities', 0);
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('api')->plainTextToken;
    }
}
