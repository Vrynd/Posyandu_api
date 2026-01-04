<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KunjunganRemaja extends Model
{
    protected $table = 'kunjungan_remaja';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'tinggi_badan',
        'imt',
        'lingkar_perut',
        'tekanan_darah',
        'gula_darah',
        'kadar_hb',
        'skrining_tbc',
        'skrining_mental',
        'edukasi',
    ];

    protected function casts(): array
    {
        return [
            'tinggi_badan' => 'decimal:1',
            'lingkar_perut' => 'decimal:1',
            'gula_darah' => 'decimal:1',
            'skrining_tbc' => 'array',
            'skrining_mental' => 'array',
            'edukasi' => 'array',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'id', 'id');
    }
}
