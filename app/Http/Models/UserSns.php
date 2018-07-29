<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UserSns extends Model
{
    protected $table='user_sns';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
