<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'nullable|string|max:5000',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'text.max' => 'Message text cannot exceed 5000 characters',
            'file.image' => 'File must be an image',
            'file.mimes' => 'Image must be of type: jpeg, png, jpg, gif, webp',
            'file.max' => 'Image size cannot exceed 2MB',
        ];
    }
}
