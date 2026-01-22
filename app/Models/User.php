<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'city_id',
        'branch_id',
        'user_login',
        'user_password_hash',
        'user_full_name',
        'user_is_active',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'user_password_hash',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (empty($user->user_password_hash) && !empty($user->password)) {
                $user->user_password_hash = $user->password;
            }
        });
    }

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
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'user_cities', 'user_id', 'city_id', 'id', 'city_id');
    }
}
