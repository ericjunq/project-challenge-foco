<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReserveRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],

            'guests' => ['required', 'array', 'min:1'],
            'guests.*.name' => ['required', 'string', 'max:100'],
            'guests.*.last_name' => ['required', 'string', 'max:100'],
            'guests.*.phone' => ['required', 'string', 'max:15'],

            'dailies' => ['required', 'array', 'min:1'],
            'dailies.*.date' => ['required', 'date_format:Y-m-d'],
            'dailies.*.value' => ['required', 'numeric', 'min:0'],

            'payments' => ['sometimes', 'array'],
            'payments.*.method' => ['required', 'integer', 'min:0', 'max:255'],
            'payments.*.value' => ['required', 'numeric', 'min:0'],
        ];
    }
}
