<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaBalita extends Model
{
    protected $table = 'peserta_balita';
    protected $primaryKey = 'peserta_id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'peserta_id',
        'nama_ortu',
    ];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'peserta_id');
    }
}
