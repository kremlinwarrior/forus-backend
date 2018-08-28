<?php

namespace App\Http\Resources;

use App\Models\Voucher;
use Illuminate\Http\Resources\Json\Resource;

class VoucherResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /**
         * @var Voucher $voucher
         */
        $voucher = $this->resource;

        return collect($voucher)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'fund' => collect($voucher->fund)->only([
                'id', 'name', 'state'
            ])->merge([
                'organization' => collect($voucher->fund->organization)->only([
                    'id', 'name'
                ])->merge([
                    'logo' => new MediaCompactResource($voucher->fund->organization->logo)
                ]),
                'logo' => new MediaCompactResource($voucher->fund->logo),
                'product_categories' => ProductCategoryResource::collection(
                    $voucher->fund->product_categories
                )
            ]),
            'transactions' => VoucherTransactionResource::collection(
                $this->resource->transactions
            ),
        ])->toArray();

        return collect($this->resource)->only([
            'identity_address', 'fund_id', 'created_at', 'address'
        ])->merge([
            'fund' => new FundResource($this->resource->fund),
            'transactions' => VoucherTransactionResource::collection(
                $this->resource->transactions
            ),
        ])->toArray();
    }
}
