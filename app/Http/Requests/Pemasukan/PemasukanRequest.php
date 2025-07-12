<?php

namespace App\Http\Requests\Pemasukan;

use Illuminate\Foundation\Http\FormRequest;

class PemasukanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'jumlah' => 'required|numeric|min:0.01',
            'tanggal' => 'required|date|before_or_equal:today',
            'deskripsi' => 'nullable|string',
            'kategori_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $kategori = \App\Models\KategoriPemasukan::where('id', $value)
                        ->where('user_id', auth()->id())
                        ->first();

                    if (!$kategori) {
                        $fail('Kategori tidak valid atau tidak ditemukan');
                    }
                }
            ],
            'bukti_transaksi' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
            'lokasi' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];

        // Validasi lokasi
        if ($this->filled('latitude') || $this->filled('longitude')) {
            $rules['latitude'] = 'required_with:longitude|numeric|between:-90,90';
            $rules['longitude'] = 'required_with:latitude|numeric|between:-180,180';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'jumlah.required' => 'Jumlah pemasukan wajib diisi',
            'jumlah.numeric' => 'Jumlah harus berupa angka',
            'jumlah.min' => 'Jumlah minimal Rp 0.01',
            'tanggal.required' => 'Tanggal wajib diisi',
            'tanggal.date' => 'Format tanggal tidak valid',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini',
            'bukti_transaksi.image' => 'File harus berupa gambar',
            'bukti_transaksi.mimes' => 'Format gambar harus jpeg, png, atau jpg',
            'bukti_transaksi.max' => 'Ukuran gambar maksimal 2MB',
            'kategori_id.required' => 'Kategori wajib dipilih',
            'latitude.required_with' => 'Latitude wajib diisi jika longitude diisi',
            'longitude.required_with' => 'Longitude wajib diisi jika latitude diisi',
            'latitude.between' => 'Latitude harus antara -90 sampai 90',
            'longitude.between' => 'Longitude harus antara -180 sampai 180',
        ];
    }
}