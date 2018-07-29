<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class UserSource extends Model
{
    protected $table='user_source';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
