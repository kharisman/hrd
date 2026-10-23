<?php

namespace App\Providers;

use App\Models\Akun;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();

        // Admin HRD = admin lokal (tabel users) atau akun eUniversity berperan admin
        Gate::define('admin', fn ($user) => $user instanceof User || $user->peran === 'admin');

        // Aksi ke API eUniversity: hanya akun eUniversity yang login (punya token) dengan scope yang cukup.
        // Pemakaian: Gate::allows('api', 'dosen.create') atau Gate::allows('api', ['staff.read', 'dosen.read'])
        Gate::define('api', fn ($user, $scopes) => $user instanceof Akun && session()->has('pct_token') && $user->bisa((array) $scopes));
    }
}
