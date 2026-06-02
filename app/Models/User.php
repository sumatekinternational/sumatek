<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Roles/permissions live under the "web" guard. Pin it here so checks
     * resolve consistently whether the request is authenticated via the web
     * session or a Sanctum token (which makes "sanctum" the active guard).
     */
    protected string $guard_name = 'web';

    /**
     * Users are provisioned by the vendor (agencies do not self-register, §2).
     * tenant_id is null for vendor control-plane users.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'locale',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
