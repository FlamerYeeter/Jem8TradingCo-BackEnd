<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Allow either top-level name fields or a nested `contact` object
            'first_name' => 'required_without:contact.name|string|max:255',
            'last_name'  => 'required_without:contact.name|string|max:255',
            'email'      => 'required_without:contact.email|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'message'    => 'nullable|string|max:2000',
            'contact'    => 'nullable|array',
            'contact.name' => 'required_without:first_name|string|max:255',
            'contact.email' => 'required_without:email|email|max:255',
            'contact.phone' => 'nullable|string|max:50',
            'contact.company' => 'nullable|string|max:255',
            'items'      => 'nullable|array',
            'items.*.product_id' => 'required_with:items|numeric',
            'items.*.quantity'   => 'required_with:items|numeric|min:1',
            'order_reference' => 'nullable|numeric',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,txt|max:5120',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required_without' => 'First name is required',
            'last_name.required_without' => 'Last name is required',
            'contact.name.required_without' => 'Contact name is required',
        ];
    }
}
