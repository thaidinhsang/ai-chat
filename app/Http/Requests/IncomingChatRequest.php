<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomingChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page_id' => ['nullable', 'string'],
            'customer_external_id' => ['required', 'string'],
            'message' => ['required', 'string'],
            'product_code' => ['nullable', 'string'],
            'deal_price' => ['nullable', 'numeric'], // nếu gửi kèm thì upsert
            'currency' => ['nullable', 'string', 'max:8'],
            'customer_name' => ['nullable', 'string'],
            'customer_phone' => ['nullable', 'string'],
            'message_external_id' => ['nullable', 'string'],
        ];
    }
}
