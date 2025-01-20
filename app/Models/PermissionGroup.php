<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class PermissionGroup extends Model
{
    //
    protected $table = 'permission_groups';

    protected $fillable = [
        'name',
        'description'
    ];

    public function permissions(){
        return $this->hasMany(Permission::class,'group_id');
    }
}
