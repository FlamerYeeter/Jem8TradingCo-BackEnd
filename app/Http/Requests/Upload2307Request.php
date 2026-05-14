<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Upload2307Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'form_2307' => 'required|file|mimes:pdf,jpeg,png,jpg|max:10240',
        ];
    }
}
