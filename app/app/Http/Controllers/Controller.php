<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function isAdmin(): bool
    {
        return \Illuminate\Support\Facades\Gate::allows('admin');
    }

    // hrd_staffs.id milik pegawai yang login (null untuk dosen non-staff / akun belum tertaut)
    protected function myStaffId(): ?int
    {
        return auth('pegawai')->user()?->staff_id;
    }

    // Semua akun staff milik orang yang login (satu NIP bisa punya beberapa akun, mis. beda divisi)
    protected function myStaffIds(): \Illuminate\Support\Collection
    {
        $id = $this->myStaffId();
        $nip = $id ? \App\Models\Staff::whereKey($id)->value('nip') : null;
        return $nip ? \App\Models\Staff::where('nip', $nip)->pluck('id') : collect($id ? [$id] : []);
    }
}
