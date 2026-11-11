<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSelectController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $tenants = $request->user()
            ->tenants()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($tenants->count() === 1) {
            TenantContext::set($tenants->first());

            return redirect()->intended(route('dashboard'));
        }

        return view('tenant.select', [
            'tenants' => $tenants,
            'currentTenantId' => TenantContext::id(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);

        if (! TenantContext::userCanAccess($tenant) || ! $tenant->is_active) {
            return back()->withErrors(['tenant_id' => 'No puedes acceder a este tenant.']);
        }

        TenantContext::set($tenant);

        return redirect()->intended(route('dashboard'));
    }
}
