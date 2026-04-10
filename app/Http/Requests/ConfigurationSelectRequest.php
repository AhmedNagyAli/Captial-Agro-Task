<?php

namespace App\Http\Requests;

use App\Models\Option;
use Illuminate\Foundation\Http\FormRequest;

class ConfigurationSelectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'config_id' => 'required|exists:configurations,id',
            'option_id' => 'required|exists:options,id',
            'quantity' => 'sometimes|integer|min:1|max:99'
        ];
    }

    public function getOption(): Option
    {
        return Option::with('optionGroup')->findOrFail($this->option_id);
    }

    public function getQuantity(): int
    {
        return $this->input('quantity', 1);
    }
}