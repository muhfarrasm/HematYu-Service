<?php

namespace App\Http\Requests\Pengeluaran;

use Illuminate\Foundation\Http\FormRequest;

class PengeluaranRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'jumlah' => 'required|numeric|min:0.01',
            'deskripsi' => 'nullable|string|max:500',
            'tanggal' => 'required|date|before_or_equal:today',
            'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'lokasi' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'kategori_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $kategori = \App\Models\KategoriPengeluaran::where('id', $value)
                        ->where('user_id', auth()->id())
                        ->first();
                    
                    if (!$kategori) {
                        $fail('Kategori tidak valid atau tidak ditemukan');
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'jumlah.required' => 'Jumlah pengeluaran wajib diisi',
            'jumlah.numeric' => 'Jumlah harus berupa angka',
            'jumlah.min' => 'Jumlah minimal Rp 0.01',
            'tanggal.required' => 'Tanggal wajib diisi',
            'tanggal.date' => 'Format tanggal tidak valid',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini',
            'bukti_transaksi.image' => 'File harus berupa gambar',
            'bukti_transaksi.mimes' => 'Format gambar harus jpeg, png, atau jpg',
            'bukti_transaksi.max' => 'Ukuran gambar maksimal 2MB',
            'kategori_id.required' => 'Kategori wajib dipilih',
            'latitude.between' => 'Latitude harus antara -90 sampai 90',
            'longitude.between' => 'Longitude harus antara -180 sampai 180',
        ];
    }
}