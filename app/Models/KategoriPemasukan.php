<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPemasukan extends Model
{
    use HasFactory;

    protected $table = 'kategori_pemasukan';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'user_id',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pemasukan()
    {
        return $this->hasMany(Pemasukan::class, 'kategori_id');
    }

    // Helper Methods
    public function getTotalPemasukan($month = null, $year = null)
    {
        $query = $this->pemasukan();
        
        if ($month && $year) {
            $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
        }
        
        return $query->sum('jumlah');
    }
}