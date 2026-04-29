<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class AuthHelper
{
    public static function user(): ?array
    {
        return request()->input('auth_user');
    }

    public static function id(): ?string
    {
        return self::user()['sub'] ?? null;
    }

    public static function email(): ?string
    {
        return self::user()['email'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }
}
