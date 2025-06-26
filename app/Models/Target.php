<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    protected $table = 'target';

    protected $fillable = [
        'nama_target',
        'target_dana',
        'terkumpul',
        'target_tanggal',
        'deskripsi',
        'status',
        'user_id',
    ];

    protected $casts = [
        'target_dana' => 'decimal:2',
        'terkumpul' => 'decimal:2',
        'target_tanggal' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relasiPemasukan()
    {
        return $this->hasMany(RelasiTargetPemasukan::class, 'id_target');
    }

    // Helper Methods
    public function getPersentaseTercapai()
    {
        if ($this->target_dana == 0) return 0;
        return ($this->terkumpul / $this->target_dana) * 100;
    }

    public function getSisaTarget()
    {
        return $this->target_dana - $this->terkumpul;
    }

    public function updateTerkumpul()
    {
        $totalTerkumpul = $this->relasiPemasukan()->sum('jumlah_alokasi');
        $this->update(['terkumpul' => $totalTerkumpul]);
        
        // Update status
        if ($this->terkumpul >= $this->target_dana) {
            $this->update(['status' => 'tercapai']);
        } elseif ($this->target_tanggal < now()) {
            $this->update(['status' => 'tidak_tercapai']);
        }
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}