<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaBumil extends Model
{
    protected $table = 'peserta_bumil';
    protected $primaryKey = 'peserta_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'peserta_id',
        'nama_suami',
        'hamil_anak_ke',
        'jarak_anak',
        'bb_sebelum_hamil',
        'tinggi_badan',
    ];

    protected function casts(): array
    {
        return [
            'bb_sebelum_hamil' => 'decimal:1',
            'tinggi_badan' => 'decimal:1',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }
}
