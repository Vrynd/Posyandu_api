<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaduanImage extends Model
{
    protected $table = 'pengaduan_images';

    protected $fillable = [
        'pengaduan_id',
        'image_path',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }
}
