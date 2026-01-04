<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nik',
        'nik_hash',
        'phone_number',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'nik_hash', // Hide hash from API responses
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'nik' => 'encrypted',
            'phone_number' => 'encrypted',
        ];
    }

    /**
     * Generate hash for NIK (used for lookups)
     */
    public static function hashNik(string $nik): string
    {
        return hash('sha256', $nik);
    }

    /**
     * Find user by NIK
     */
    public static function findByNik(string $nik): ?self
    {
        return self::where('nik_hash', self::hashNik($nik))->first();
    }

    /**
     * Find user by email or NIK
     */
    public static function findByEmailOrNik(string $identifier): ?self
    {
        // Check if identifier looks like an email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return self::where('email', $identifier)->first();
        }

        // Otherwise, try to find by NIK
        return self::findByNik($identifier);
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'created_by');
    }

    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isKader(): bool
    {
        return $this->role === 'kader';
    }
}
