<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaRemaja extends Model
{
    protected $table = 'peserta_remaja';
    protected $primaryKey = 'peserta_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'peserta_id',
        'nama_ortu',
        'riwayat_keluarga',
        'perilaku_berisiko',
    ];

    protected function casts(): array
    {
        return [
            'riwayat_keluarga' => 'array',
            'perilaku_berisiko' => 'array',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }
}
