<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    public function test_returns_ok_status_for_the_service(): void
    {
        $response = $this->getJson(route('v1.health'));

        $response->assertOk();
        $response->assertExactJson([
            'data' => [
                'status' => 'ok',
                'service' => 'Winx',
            ],
        ]);
    }
}
