<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\ProductCategoryResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProviderIdentity;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\Resource;

class ProviderVoucherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $identityAddress = request()->get('identity');

        /**
         * @var Voucher $voucher
         */
        $voucher = $this->resource;
        $amountLeft = $voucher->amount - $voucher->transactions->sum('amount');

        $voucherOrganizations = $voucher->fund->providers->pluck('organization');

        $allowedOrganizations = Organization::getModel()->where(function(
            Builder $query
        ) use (
            $identityAddress
        ) {
            return $query->where([
                'identity_address' => $identityAddress
            ])->orWhereIn('id', ProviderIdentity::getModel()->where([
                'identity_address' => $identityAddress
            ])->pluck('provider_id')->unique()->toArray());
        })->whereIn('id', $voucherOrganizations->pluck('id'))->get();

        $allowedProductCategories = $voucher->fund->product_categories;
        $allowedProducts = Product::getModel()->whereIn(
            'organization_id', $allowedOrganizations->pluck('id')
        )->whereIn(
            'product_category_id', $allowedProductCategories->pluck('id')
        )->get();

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'amount' => max($amountLeft, 0),
            'fund' => collect($voucher->fund)->only([
                'id', 'name', 'state'
            ])->merge([
                'organization' => collect($voucher->fund->organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($voucher->fund->organization->logo)
                ]),
                'logo' => new MediaCompactResource($voucher->fund->logo)
            ]),
            'allowed_organizations' => collect(
                $allowedOrganizations
            )->map(function($organization) {
                return collect($organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($organization->logo)
                ]);
            }),
            'allowed_product_categories' => ProductCategoryResource::collection(
                $allowedProductCategories
            ),
            'allowed_products' => collect($allowedProducts)->map(function($product) {
                /** @var Product $product */
                return collect($product)->only([
                    'id', 'name', 'description', 'price', 'old_price',
                    'total_amount', 'sold_amount'
                ])->merge([
                    'photo' => new MediaCompactResource($product->photo),
                    'product_category' => new ProductCategoryResource(
                        $product->product_category
                    )
                ]);
            }),
        ])->toArray();
    }
}
