<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
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
            'name' => 'required',
            'phone' => 'required',
            'type_delivery' => 'required',
            'type_pay' => 'required',
            'person' => 'required',
            'items' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'body.required' => 'A message is required',
        ];
    }
}
