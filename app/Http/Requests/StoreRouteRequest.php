<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_code' => ['required', 'string', 'max:255', 'unique:routes,route_code'],
            'route_district' => ['nullable', 'string', 'max:255'],
            'route_type' => ['required', 'in:city,private,mixed'],
            'boxes_count' => ['required', 'integer', 'min:0'],
            'entrances_count' => ['required', 'integer', 'min:0'],
            'route_comment' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
