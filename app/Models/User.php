<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_global'
    ];

    protected $hidden = ['password'];

    // Relasi ke tenant (bisa multi-tenant via pivot)
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'role_user')
                    ->withPivot('role_id')
                    ->withTimestamps();
    }

    // Relasi ke roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
                    ->withPivot('tenant_id')
                    ->withTimestamps();
    }

    // Relasi ke subroles
    public function subRoles()
    {
        return $this->belongsToMany(SubRole::class, 'sub_role_user')
                    ->withPivot('tenant_id')
                    ->withTimestamps();
    }

    // 🔒 Cek apakah user punya role di tenant tertentu
    public function hasRole($roleName, $tenantId = null)
    {
        return $this->roles()
            ->when($tenantId, fn($q) => $q->wherePivot('tenant_id', $tenantId))
            ->where('name', $roleName)
            ->exists();
    }

    // 🔒 Cek apakah user punya subrole
    public function hasSubRole($subRoleName, $tenantId = null)
    {
        return $this->subRoles()
            ->when($tenantId, fn($q) => $q->wherePivot('tenant_id', $tenantId))
            ->where('name', $subRoleName)
            ->exists();
    }

    /**
     * Scope untuk menampilkan user sesuai tenant
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\Tenant $tenant
     * @param bool $includeGlobal
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleForTenant($query, $tenant = null, $includeGlobal = false)
{
    return $query->where(function ($q) use ($tenant, $includeGlobal) {
        if ($tenant) {
            // User tenant yang punya role di tenant aktif
            $q->where('is_global', false)
              ->whereHas('roles', function ($roleQ) use ($tenant) {
                  $roleQ->where('role_user.tenant_id', $tenant->id);
              });
        }

        // Jika ingin menampilkan global juga
        if ($includeGlobal) {
            $q->orWhere('is_global', true);
        }
    });
}

}
