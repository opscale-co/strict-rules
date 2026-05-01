<?php

namespace Opscale\Http;

use Opscale\Models\Product;
use Opscale\Models\Tenant;
use Opscale\Models\User;

class MultiModelFormRequest
{
    /**
     * Models exposed by this request — declarative metadata, not operations.
     *
     * @var array<int, class-string>
     */
    public const RESOURCE_CLASSES = [
        User::class,
        Product::class,
        Tenant::class,
    ];

    public function authorize(User $user, Product $product, Tenant $tenant): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }
}
