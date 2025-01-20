<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
   protected $table = 'companies';

   protected $fillable = [
       'uuid', 
       'name', 
       'description', 
       'logo', 
       'phone_number', 
       'address', 
       'default_role',
   ];
}
