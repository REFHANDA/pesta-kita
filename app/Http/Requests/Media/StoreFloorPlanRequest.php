<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class StoreFloorPlanRequest extends FormRequest
{
    /**
     * Determine if the authenticated provider may submit a floor plan.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Validate the venue plan file.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
        ];
    }
}
