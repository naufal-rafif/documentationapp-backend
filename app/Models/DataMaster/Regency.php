<?php

namespace App\Models\DataMaster;

use Illuminate\Database\Eloquent\Model;

class Regency extends Model
{
    protected $table = 'regencies';

    protected $fillable = [
        'uuid',
        'name',
        'alt_name',
        'latitude',
        'longitude',
        'province_id',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    // public function districts()
    // {
    //     return $this->hasMany(District::class);
    // }
}
