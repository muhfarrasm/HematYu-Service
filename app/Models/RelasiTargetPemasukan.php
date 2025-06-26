<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelasiTargetPemasukan extends Model
{
    use HasFactory;

    protected $table = 'relasi_target_pemasukan';

    protected $fillable = [
        'id_target',
        'id_pemasukan',
        'jumlah_alokasi',
    ];

    protected $casts = [
        'jumlah_alokasi' => 'decimal:2',
    ];

    // Relationships
    public function target()
    {
        return $this->belongsTo(Target::class, 'id_target');
    }

    public function pemasukan()
    {
        return $this->belongsTo(Pemasukan::class, 'id_pemasukan');
    }

    // Boot method to update target when relasi changes
    protected static function boot()
    {
        parent::boot();

        static::created(function ($relasi) {
            $relasi->target->updateTerkumpul();
        });

        static::updated(function ($relasi) {
            $relasi->target->updateTerkumpul();
        });

        static::deleted(function ($relasi) {
            $relasi->target->updateTerkumpul();
        });
    }
}