<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{status: string, service: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resource['status'],
            'service' => $this->resource['service'],
        ];
    }
}
