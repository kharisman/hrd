<?php

namespace App\Services;

// Tidak ada token eUniversity yang valid (token kedaluwarsa/dicabut, atau session hilang).
// Ditangani di Exceptions\Handler: kembali ke halaman sebelumnya dengan pesan.
class SesiApiHabis extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Aksi ini butuh login dengan akun eUniversity. Sesi eUniversity Anda tidak ada atau sudah berakhir, silakan keluar lalu login ulang.');
    }
}
