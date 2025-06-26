<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriTarget extends Model
{
    use HasFactory;

    protected $table = 'kategori_target';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'user_id'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function targets()
    {
        return $this->hasMany(Target::class);
    }

    // Helper Methods
    /**
     * Get total collected amount for this category
     */
    public function getTotalTerkumpul()
    {
        return $this->targets()->sum('terkumpul');
    }

    /**
     * Get total target amount for this category
     */
    public function getTotalTarget()
    {
        return $this->targets()->sum('target_dana');
    }

    /**
     * Calculate achievement percentage for this category
     */
    public function getPersentasePencapaian()
    {
        $totalTarget = $this->getTotalTarget();
        if ($totalTarget == 0) return 0;
        
        return ($this->getTotalTerkumpul() / $totalTarget) * 100;
    }

    /**
     * Get active targets count
     */
    public function getJumlahTargetAktif()
    {
        return $this->targets()
            ->where('status', 'aktif')
            ->count();
    }

    /**
     * Get completed targets count
     */
    public function getJumlahTargetTercapai()
    {
        return $this->targets()
            ->where('status', 'tercapai')
            ->count();
    }

    /**
     * Get unachieved targets count
     */
    public function getJumlahTargetTidakTercapai()
    {
        return $this->targets()
            ->where('status', 'tidak_tercapai')
            ->count();
    }

    /**
     * Get targets grouped by status
     */
    public function getTargetsByStatus()
    {
        return $this->targets()
            ->selectRaw('status, count(*) as count, sum(target_dana) as total_target, sum(terkumpul) as total_terkumpul')
            ->groupBy('status')
            ->get();
    }
}