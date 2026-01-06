<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengaduan extends Model
{
    use SoftDeletes;

    protected $table = 'pengaduan';

    protected $fillable = [
        'user_id',
        'kategori_new',
        'prioritas',
        'judul',
        'deskripsi',
        'langkah_reproduksi',
        'browser_info',
        'status_new',
    ];

    protected $appends = ['kategori', 'status'];

    // Accessor for kategori (from kategori_new column)
    public function getKategoriAttribute(): ?string
    {
        return $this->kategori_new;
    }

    // Accessor for status (from status_new column)
    public function getStatusAttribute(): ?string
    {
        return $this->status_new;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PengaduanImage::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PengaduanResponse::class)->orderBy('created_at', 'asc');
    }
}
