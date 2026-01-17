<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromoterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Laravel подставит модель Promoter из /promoters/{promoter}
        $promoter = $this->route('promoter');
        $promoterId = $promoter?->promoter_id;

        return [
            'promoter_full_name' => ['required', 'string', 'max:255'],
            'promoter_phone' => ['nullable', 'digits:10', 'unique:promoters,promoter_phone,' . $promoterId . ',promoter_id'],
            'promoter_status' => ['required', 'in:active,trainee,paused,fired'],
            'hired_at' => ['nullable', 'date'],
            'fired_at' => ['nullable', 'date'],
            'promoter_comment' => ['nullable', 'string', 'max:255'],
        ];
    }
}
