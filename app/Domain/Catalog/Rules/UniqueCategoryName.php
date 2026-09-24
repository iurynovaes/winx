<?php

namespace App\Domain\Catalog\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueCategoryName implements ValidationRule
{
    public function __construct(private ?int $ignoreId = null) {}

    /**
     * Reject a category name that already exists, ignoring letter case.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $exists = Category::query()
            ->whereRaw('lower(nome) = ?', [Str::lower($value)])
            ->when($this->ignoreId !== null, fn ($query) => $query->whereKeyNot($this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('Esta categoria já está em uso.');
        }
    }
}
