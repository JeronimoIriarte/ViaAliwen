<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestinoImagenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige rol:admin
    }

    public function rules(): array
    {
        return [
            'imagenes' => ['required', 'array', 'min:1', 'max:10'],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'epigrafe' => ['nullable', 'string', 'max:150'],
            'credito' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'imagenes.required' => 'Elegí al menos una imagen.',
            'imagenes.max' => 'Podés subir hasta 10 imágenes por vez.',
            'imagenes.*.max' => 'Cada imagen puede pesar hasta 4 MB.',
        ];
    }
}
