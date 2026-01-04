<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Pengaduan extends Model
{
    use HasUuids;
    protected $table = 'pengaduan';

    protected $fillable = [
        'user_id',
        'kategori',
        'judul',
        'deskripsi',
        'foto_url',
        'status',
        'balasan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
