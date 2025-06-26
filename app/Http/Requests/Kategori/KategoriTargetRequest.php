<?php

namespace App\Http\Requests\Kategori;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriTargetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = auth()->id();
        $kategoriId = $this->route('kategori') ? $this->route('kategori')->id : null;

        return [
            'nama_kategori' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($userId, $kategoriId) {
                    $query = \App\Models\KategoriTarget::where('nama_kategori', $value)
                        ->where('user_id', $userId);
                    
                    if ($kategoriId) {
                        $query->where('id', '!=', $kategoriId);
                    }
                    
                    if ($query->exists()) {
                        $fail('Nama kategori sudah digunakan');
                    }
                }
            ],
            'deskripsi' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'warna' => 'nullable|string|max:20',
        ];
    }

    public function messages()
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter',
            'icon.max' => 'Icon maksimal 50 karakter',
            'warna.max' => 'Warna maksimal 20 karakter',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth()->id()
        ]);
    }
}