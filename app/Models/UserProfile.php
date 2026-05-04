<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'company_name',
        'tax_number',
        'address',
        'city',
        'postal_code',
        'country',
        'currency',
        'logo_path',
    ];
}
