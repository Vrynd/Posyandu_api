<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peserta extends Model
{
    protected $table = 'peserta';

    protected $fillable = [
        'nik',
        'nik_hash',
        'nama',
        'kategori',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'rt',
        'rw',
        'telepon',
        'kepesertaan_bpjs',
        'nomor_bpjs',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'kepesertaan_bpjs' => 'boolean',
            'nik' => 'encrypted',
            'telepon' => 'encrypted',
        ];
    }

    /**
     * Helper to generate NIK hash for searching
     */
    public static function hashNik(string $nik): string
    {
        return hash('sha256', $nik);
    }

    /**
     * Find peserta by NIK hash
     */
    public static function findByNik(string $nik): ?self
    {
        return self::where('nik_hash', self::hashNik($nik))->first();
    }

    protected $appends = ['extension', 'last_kunjungan_date'];

    public function loadExtension()
    {
        $relation = match ($this->kategori) {
            'bumil' => 'bumil',
            'balita' => 'balita',
            'remaja' => 'remaja',
            'produktif', 'lansia' => 'dewasa',
            default => null,
        };

        return $relation ? $this->load($relation) : $this;
    }

    public function getExtensionAttribute()
    {
        return match ($this->kategori) {
            'bumil' => $this->bumil,
            'balita' => $this->balita,
            'remaja' => $this->remaja,
            'produktif', 'lansia' => $this->dewasa,
            default => null,
        };
    }

    public function bumil(): HasOne
    {
        return $this->hasOne(PesertaBumil::class, 'peserta_id');
    }

    public function balita(): HasOne
    {
        return $this->hasOne(PesertaBalita::class, 'peserta_id');
    }

    public function remaja(): HasOne
    {
        return $this->hasOne(PesertaRemaja::class, 'peserta_id');
    }

    public function dewasa(): HasOne
    {
        return $this->hasOne(PesertaDewasa::class, 'peserta_id');
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'peserta_id');
    }

    public function latestKunjungan(): HasOne
    {
        return $this->hasOne(Kunjungan::class, 'peserta_id')->latestOfMany('tanggal_kunjungan');
    }

    public function getLastKunjunganDateAttribute(): ?string
    {
        return $this->latestKunjungan?->tanggal_kunjungan?->format('Y-m-d');
    }
}
