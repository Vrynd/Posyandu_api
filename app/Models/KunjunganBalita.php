<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KunjunganBalita extends Model
{
    protected $table = 'kunjungan_balita';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'umur_bulan',
        'kesimpulan_bb',
        'panjang_badan',
        'lingkar_kepala',
        'lingkar_lengan',
        'skrining_tbc',
        'balita_mendapatkan',
        'edukasi_konseling',
        'ada_gejala_sakit',
    ];

    protected function casts(): array
    {
        return [
            'panjang_badan' => 'decimal:1',
            'lingkar_kepala' => 'decimal:1',
            'lingkar_lengan' => 'decimal:1',
            'skrining_tbc' => 'array',
            'balita_mendapatkan' => 'array',
            'edukasi_konseling' => 'array',
            'ada_gejala_sakit' => 'boolean',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'id', 'id');
    }
}
