<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran';

    protected $fillable = [
        'jumlah',
        'deskripsi',
        'tanggal',
        'bukti_transaksi',
        'lokasi',
        'latitude',
        'longitude',
        'kategori_id',
        'user_id',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'tanggal' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_id');
    }

    // Accessors
    public function getBuktiTransaksiUrlAttribute()
    {
        if ($this->bukti_transaksi) {
            return asset('storage/bukti_transaksi/' . $this->bukti_transaksi);
        }
        return null;
    }

    // Scopes
    public function scopeByMonth($query, $month, $year)
    {
        return $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}