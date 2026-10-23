<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

// API eUniversity menolak permintaan (bentrok / validasi / tidak ditemukan)
class ApiDitolak extends \RuntimeException
{
    public function __construct(string $pesan, public array $errors = [])
    {
        parent::__construct($pesan);
    }

    // Tampilkan kembali di form: error per kolom (nama kolom API = nama input form) + pesan umum
    public function keForm(): ValidationException
    {
        $errors = $this->errors ?: ['api' => [$this->getMessage()]];
        return ValidationException::withMessages(['api' => 'eUniversity: ' . $this->getMessage()] + $errors);
    }
}
