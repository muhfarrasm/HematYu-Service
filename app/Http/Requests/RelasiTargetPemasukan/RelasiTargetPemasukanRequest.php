<?php

namespace App\Http\Requests\RelasiTargetPemasukan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RelasiTargetPemasukanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'id_target' => [
                'required',
                'exists:target,id',
                function ($attribute, $value, $fail) {
                    $target = \App\Models\Target::find($value);
                    if (!$target) return;
                    
                    if ($target->user_id !== auth()->id()) {
                        $fail('Anda tidak memiliki akses ke target ini');
                    }
                    
                    if ($target->status !== 'aktif') {
                        $fail('Target sudah tidak aktif');
                    }
                }
            ],
            'id_pemasukan' => [
                'required',
                'exists:pemasukan,id',
                function ($attribute, $value, $fail) {
                    $pemasukan = \App\Models\Pemasukan::find($value);
                    if (!$pemasukan) return;
                    
                    if ($pemasukan->user_id !== auth()->id()) {
                        $fail('Anda tidak memiliki akses ke pemasukan ini');
                    }
                },
                Rule::unique('relasi_target_pemasukan')
                    ->where('id_target', $this->id_target)
                    ->ignore($this->route('relasi'))
            ],
            'jumlah_alokasi' => 'required|numeric|min:1000',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'id_target.required' => 'Target wajib dipilih',
            'id_target.exists' => 'Target tidak valid',
            'id_pemasukan.required' => 'Pemasukan wajib dipilih',
            'id_pemasukan.exists' => 'Pemasukan tidak valid',
            'id_pemasukan.unique' => 'Pemasukan sudah dialokasikan ke target ini',
            'jumlah_alokasi.required' => 'Jumlah alokasi wajib diisi',
            'jumlah_alokasi.numeric' => 'Jumlah alokasi harus berupa angka',
            'jumlah_alokasi.min' => 'Jumlah alokasi minimal Rp :min',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->any() && $this->filled('id_target') && $this->filled('id_pemasukan')) {
                $this->validateSisaDana($validator);
            }
        });
    }

    protected function validateSisaDana($validator)
    {
        $target = \App\Models\Target::find($this->id_target);
        $pemasukan = \App\Models\Pemasukan::find($this->id_pemasukan);
        $relasi = $this->route('relasi');

        // Hitung sisa pemasukan
        $totalAlokasiPemasukan = $pemasukan->relasiTarget()->sum('jumlah_alokasi');
        $sisaPemasukan = $pemasukan->jumlah - ($relasi ? ($totalAlokasiPemasukan - $relasi->jumlah_alokasi) : $totalAlokasiPemasukan);

        if ($this->jumlah_alokasi > $sisaPemasukan) {
            $validator->errors()->add('jumlah_alokasi', 
                'Jumlah alokasi melebihi sisa pemasukan. Sisa: Rp ' . number_format($sisaPemasukan, 2));
        }

        // Hitung sisa target
        $sisaTarget = $target->target_dana - $target->terkumpul + ($relasi ? $relasi->jumlah_alokasi : 0);
        if ($this->jumlah_alokasi > $sisaTarget) {
            $validator->errors()->add('jumlah_alokasi', 
                'Jumlah alokasi melebihi sisa target. Sisa: Rp ' . number_format($sisaTarget, 2));
        }
    }
}