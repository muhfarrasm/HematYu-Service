<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function kategoriPemasukan()
    {
        return $this->hasMany(KategoriPemasukan::class);
    }

    public function kategoriPengeluaran()
    {
        return $this->hasMany(KategoriPengeluaran::class);
    }

    public function pemasukan()
    {
        return $this->hasMany(Pemasukan::class);
    }

    public function pengeluaran()
    {
        return $this->hasMany(Pengeluaran::class);
    }

    public function target()
    {
        return $this->hasMany(Target::class);
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

    public function getTotalPengeluaran($month = null, $year = null)
    {
        $query = $this->pengeluaran();
        
        if ($month && $year) {
            $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
        }
        
        return $query->sum('jumlah');
    }

    public function getSisaSaldo($month = null, $year = null)
    {
        return $this->getTotalPemasukan($month, $year) - $this->getTotalPengeluaran($month, $year);
    }
}