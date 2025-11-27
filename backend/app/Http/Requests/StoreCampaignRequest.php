<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'creator_name' => 'required|string|max:255',
            'creator_email' => 'required|email|max:255',
            'creator_phone' => 'required|string|max:20',
            'target_amount' => 'required|numeric|min:0',
            'payment_type' => 'required|in:bkash,nagad,rocket,bank'
        ];
    }
}
