<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

// Admin HRD (database hrd)
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
}
