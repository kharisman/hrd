<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Services\PctApi;

// Data pegawai aktif yang perlu dilengkapi: tanggal mulai kerja (syarat cuti tahunan 12 bulan) & Telegram (notifikasi)
class KelengkapanController extends Controller
{
    public function __invoke(PctApi $api)
    {
        abort_unless($this->isAdmin(), 403);
        $staff = Staff::perOrang(Staff::aktif())->sortBy('nama')->values();
        $terhubung = rescue(fn () => $api->terhubung($staff->pluck('username')->all()), null, false);
        return view('pengaturan.kelengkapan', [
            'tanpaMulai' => $staff->whereNull('mulai_kerja')->values(),
            'tanpaTelegram' => $terhubung === null ? null : $staff->filter(fn ($s) => !($terhubung[$s->username] ?? false))->values(),
            'total' => $staff->count(),
        ]);
    }
}
