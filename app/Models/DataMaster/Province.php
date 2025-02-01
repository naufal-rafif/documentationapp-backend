<?php

namespace App\Models\DataMaster;

use DB;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $table = 'provinces';

    protected $fillable = [
        'uuid',
        'name',
        'alt_name',
        'latitude',
        'longitude',
    ];

    // public function regencies()
    // {
    //     return $this->hasMany(Regency::class);
    // }
}
