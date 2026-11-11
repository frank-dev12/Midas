<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! TenantContext::check()) {
            return redirect()->route('tenant.select');
        }

        $tenant = TenantContext::tenant();

        if (! $tenant || ! $tenant->is_active || ! TenantContext::userCanAccess($tenant)) {
            TenantContext::set(null);

            return redirect()->route('tenant.select')
                ->with('error', 'Selecciona un tenant válido.');
        }

        return $next($request);
    }
}
