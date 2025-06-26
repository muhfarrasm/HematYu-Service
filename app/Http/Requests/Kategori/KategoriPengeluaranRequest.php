<?php

namespace App\Http\Requests\Kategori;

use Illuminate\Foundation\Http\FormRequest;

class KategoriPengeluaranRequest extends FormRequest
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
                    $query = \App\Models\KategoriPengeluaran::where('nama_kategori', $value)
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
            'anggaran' => 'required|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'nama_kategori.required' => 'Nama kategori wajib diisi',
            'nama_kategori.max' => 'Nama kategori maksimal 255 karakter',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter',
            'anggaran.required' => 'Anggaran wajib diisi',
            'anggaran.numeric' => 'Anggaran harus berupa angka',
            'anggaran.min' => 'Anggaran tidak boleh negatif',
        ];
    }
}