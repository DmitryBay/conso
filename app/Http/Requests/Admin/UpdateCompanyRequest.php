<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        $ownerId = $this->route('company')?->owner?->id;

        return [
            'name' => ['required', 'string', 'max:160'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', Rule::in(['IDR', 'USD', 'EUR', 'AUD'])],
            'status' => ['required', Rule::in(['trial', 'active', 'suspended'])],
            'plan' => ['required', 'string', 'max:50'],
            'rooms_count' => ['required', 'integer', 'min:0', 'max:65000'],
            'owner_name' => ['required', 'string', 'max:160'],
            'owner_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ownerId)],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'owner_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
