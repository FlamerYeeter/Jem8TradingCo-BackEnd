<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryItemsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'items' => 'required|array',
            'items.*.id' => 'nullable|integer',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }
}
