<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KunjunganDewasa extends Model
{
    protected $table = 'kunjungan_dewasa';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'tinggi_badan',
        'imt',
        'lingkar_perut',
        'tekanan_darah',
        'gula_darah',
        'asam_urat',
        'kolesterol',
        'tes_mata',
        'tes_telinga',
        'skrining_tbc',
        'skrining_puma',
        'jumlah_skor_puma',
        'alat_kontrasepsi',
        'adl',
        'jumlah_skor_adl',
        'tingkat_kemandirian',
        'edukasi',
    ];

    protected function casts(): array
    {
        return [
            'tinggi_badan' => 'decimal:1',
            'lingkar_perut' => 'decimal:1',
            'gula_darah' => 'decimal:1',
            'asam_urat' => 'decimal:1',
            'kolesterol' => 'decimal:1',
            'skrining_tbc' => 'array',
            'skrining_puma' => 'array',
            'adl' => 'array',
            'edukasi' => 'array',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'id', 'id');
    }
}
