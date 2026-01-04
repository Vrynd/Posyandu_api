<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kunjungan extends Model
{
    protected $table = 'kunjungan';

    protected $fillable = [
        'peserta_id',
        'tanggal_kunjungan',
        'berat_badan',
        'rujuk',
        'lokasi',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date',
            'berat_badan' => 'decimal:2',
            'rujuk' => 'boolean',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Note: 'detail' is NOT auto-appended to avoid circular reference with Peserta.latestKunjungan
    // Use loadDetail() method when you need the detail data explicitly.

    public function loadDetail()
    {
        $kategori = $this->peserta?->kategori;
        $relation = match ($kategori) {
            'bumil' => 'bumil',
            'balita' => 'balita',
            'remaja' => 'remaja',
            'produktif', 'lansia' => 'dewasa',
            default => null,
        };

        return $relation ? $this->load($relation) : $this;
    }

    public function getDetailAttribute()
    {
        $kategori = $this->peserta?->kategori;
        return match ($kategori) {
            'bumil' => $this->bumil,
            'balita' => $this->balita,
            'remaja' => $this->remaja,
            'produktif', 'lansia' => $this->dewasa,
            default => null,
        };
    }

    public function bumil(): HasOne
    {
        return $this->hasOne(KunjunganBumil::class, 'id', 'id');
    }

    public function balita(): HasOne
    {
        return $this->hasOne(KunjunganBalita::class, 'id', 'id');
    }

    public function remaja(): HasOne
    {
        return $this->hasOne(KunjunganRemaja::class, 'id', 'id');
    }

    public function dewasa(): HasOne
    {
        return $this->hasOne(KunjunganDewasa::class, 'id', 'id');
    }
}
