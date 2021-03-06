<?php

namespace App\Http\Requests\Api\Platform\Vouchers\Transactions;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProviderIdentity;
use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /**
         * shopkeeper identity and organizations
         */
        $identityAddress = request()->get('identity');
        $identityOrganizations = Organization::getModel()->where([
            'identity_address' => $identityAddress
        ])->orWhereIn('id', ProviderIdentity::getModel()->where([
            'identity_address' => $identityAddress
        ])->pluck('provider_id')->unique()->toArray())->pluck('id');

        /**
         * target voucher
         *
         * @var Voucher $voucher
         */
        $voucher = request()->voucher_address;
        $voucherOrganizations = $voucher->fund->providers()->where([
            'state' => 'approved'
        ])->pluck('organization_id');

        /**
         * Organization approved by voucher fund
         */
        $validOrganizations = collect($voucherOrganizations->intersect(
            $identityOrganizations
        ));

        /**
         * Product categories approved by fund
         */
        $validCategories = $voucher->fund->product_categories->pluck('id');

        /**
         * Products approved by funds
         */
        $validProductsIds = Organization::getModel()->whereIn(
            'id', $validOrganizations
        )->get()->pluck('products')->flatten()->filter(function($product) use ($validCategories) {
            /** @var Product $product */
            return $validCategories->search($product->product_category_id) !== false;
        })->pluck('id');

        return [
            'amount'            => [
                'required_without:product_id',
                'numeric',
                'min:.01'
            ],
            'product_id'        => [
                'exists:products,id',
                'in:' . $validProductsIds->implode(',')
            ],
            'organization_id'   => [
                'required',
                'exists:organizations,id',
                'in:' . $validOrganizations->implode(',')
            ]
        ];
    }
}
