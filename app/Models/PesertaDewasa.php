<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaDewasa extends Model
{
    protected $table = 'peserta_dewasa';
    protected $primaryKey = 'peserta_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'peserta_id',
        'pekerjaan',
        'status_perkawinan',
        'riwayat_diri',
        'merokok',
        'konsumsi_gula',
        'konsumsi_garam',
        'konsumsi_lemak',
    ];

    protected function casts(): array
    {
        return [
            'riwayat_diri' => 'array',
            'merokok' => 'boolean',
            'konsumsi_gula' => 'boolean',
            'konsumsi_garam' => 'boolean',
            'konsumsi_lemak' => 'boolean',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }
}
