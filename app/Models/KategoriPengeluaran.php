<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriPengeluaran extends Model
{
    use HasFactory;

    protected $table = 'kategori_pengeluaran';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'anggaran',
        'user_id',
    ];

    protected $casts = [
        'anggaran' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class, 'kategori_id');
    }

    // Helper Methods
    public function getTotalPengeluaran($month = null, $year = null)
    {
        $query = $this->pengeluaran();
        
        if ($month && $year) {
            $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
        }
        
        return $query->sum('jumlah');
    }

    public function getPersentaseAnggaran($month = null, $year = null)
    {
        if ($this->anggaran == 0) return 0;
        
        $totalPengeluaran = $this->getTotalPengeluaran($month, $year);
        return ($totalPengeluaran / $this->anggaran) * 100;
    }

    public function getSisaAnggaran($month = null, $year = null)
    {
        return $this->anggaran - $this->getTotalPengeluaran($month, $year);
    }
}