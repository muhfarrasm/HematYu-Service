<?php

namespace App\Http\Requests\Target;

use Illuminate\Foundation\Http\FormRequest;

class TargetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nama_target' => 'required|string|max:255',
            'target_dana' => 'required|numeric|min:0.01',
            'target_tanggal' => 'required|date|after:today',
            'deskripsi' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'nama_target.required' => 'Nama target wajib diisi',
            'nama_target.max' => 'Nama target maksimal 255 karakter',
            'target_dana.required' => 'Target dana wajib diisi',
            'target_dana.numeric' => 'Target dana harus berupa angka',
            'target_dana.min' => 'Target dana minimal Rp 0.01',
            'target_tanggal.required' => 'Target tanggal wajib diisi',
            'target_tanggal.date' => 'Format tanggal tidak valid',
            'target_tanggal.after' => 'Target tanggal harus setelah hari ini',
            'deskripsi.max' => 'Deskripsi maksimal 500 karakter',
        ];
    }
}