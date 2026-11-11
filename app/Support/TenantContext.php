<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantContext
{
    public const SESSION_KEY = 'current_tenant_id';

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return $id !== null ? (int) $id : null;
    }

    public static function tenant(): ?Tenant
    {
        $id = self::id();

        if (! $id) {
            return null;
        }

        return Tenant::query()->find($id);
    }

    public static function set(?Tenant $tenant): void
    {
        if ($tenant === null) {
            Session::forget(self::SESSION_KEY);

            return;
        }

        Session::put(self::SESSION_KEY, $tenant->id);
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function userCanAccess(Tenant $tenant): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->tenants()->where('tenants.id', $tenant->id)->exists();
    }
}
