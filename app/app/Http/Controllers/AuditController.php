<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

// Halaman audit log (admin): siapa melakukan apa, kapan, dari mana
class AuditController extends Controller
{
    public function __invoke(Request $r)
    {
        abort_unless($this->isAdmin(), 403);
        $log = AuditLog::query()
            ->when($r->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('nama', 'like', "%{$v}%")->orWhere('aksi', 'like', "%{$v}%")->orWhere('url', 'like', "%{$v}%")))
            ->when($r->dari, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($r->sampai, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')->paginate(50)->withQueryString();
        return view('pengaturan.audit', compact('log'));
    }
}
