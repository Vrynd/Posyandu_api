<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KunjunganBumil extends Model
{
    protected $table = 'kunjungan_bumil';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'umur_kehamilan',
        'lila',
        'tekanan_darah',
        'skrining_tbc',
        'tablet_darah',
        'asi_eksklusif',
        'mt_bumil_kek',
        'kelas_bumil',
        'penyuluhan',
    ];

    protected function casts(): array
    {
        return [
            'lila' => 'decimal:1',
            'skrining_tbc' => 'array',
            'tablet_darah' => 'boolean',
            'asi_eksklusif' => 'boolean',
            'mt_bumil_kek' => 'boolean',
            'kelas_bumil' => 'boolean',
            'penyuluhan' => 'array',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'id', 'id');
    }
}
