<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use Illuminate\Foundation\Http\FormRequest;

class StoreFundRequest extends FormRequest
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
        return [
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,1000',
            'start_date'            => 'required|date',
            'end_date'              => 'required|date',
            'product_categories'    => 'required|array',
            'product_categories.*'  => 'exists:product_categories,id',
        ];
    }
}
