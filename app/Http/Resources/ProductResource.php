<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Resources\Json\Resource;

class ProductResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Product $product */
        $product = $this->resource;
        $suppliedFundIds = $product->organization->supplied_funds_approved;

        $funds = $product->product_category->funds()->whereIn(
            'funds.id', $suppliedFundIds->pluck('id')
        )->get();

        return collect($product)->only([
            'id', 'name', 'description', 'price', 'old_price',
            'total_amount', 'sold_amount', 'product_category_id',
            'organization_id'
        ])->merge([
            'funds' => $funds->implode('name', ', '),
            'offices' => OfficeResource::collection($product->organization->offices),
            'photo' => new MediaResource(
                $product->photo
            ),
            'product_category' => new ProductCategoryResource(
                $product->product_category
            )
        ])->toArray();
    }
}
