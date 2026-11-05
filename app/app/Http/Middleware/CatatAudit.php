<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;

// Audit trail: semua perubahan (POST/PUT/DELETE) + akses data sensitif (berkas, PDF, ekspor, profil pegawai) yang berhasil
class CatatAudit
{
    // Route GET yang dicatat (lihat/unduh data sensitif)
    const SENSITIF = ['pengajuan.berkas', 'pengajuan.pdf', 'riwayat.berkas', 'staff.show', 'dosen.show', 'input.kehadiran.template'];

    public function handle(Request $request, Closure $next)
    {
        $res = $next($request);
        $nama = $request->route()?->getName() ?? $request->path();
        $ekspor = $request->isMethod('GET') && ($request->has('ekspor') || $request->has('export') || str_starts_with($nama, 'laporan.') && $request->query('format'));
        if (auth()->check() && $res->getStatusCode() < 400
            && (!$request->isMethod('GET') || in_array($nama, self::SENSITIF) || $ekspor)) {
            AuditLog::catat($nama . ($ekspor ? ' (ekspor)' : ''), $request);
        }
        return $res;
    }
}
