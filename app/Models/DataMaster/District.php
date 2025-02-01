<?php

namespace App\Models\DataMaster;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{

    protected $table = 'districts';
    protected $fillable = [
        'uuid',
        'name',
        'regency_id',
        'alt_name',
        'latitude',
        'longitude'
    ];

    // public function regency()
    // {
    //     return $this->belongsTo(Regency::class);
    // }
    
}
