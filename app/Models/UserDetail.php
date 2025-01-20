<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $table = 'user_details';

    protected $fillable = [
        'user_id',
        'full_name',
        'address',
        'avatar',
        'phone_number',
        'birth_date',
        'gender',
        'status_account',
    ];
}
