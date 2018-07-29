<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Active extends Model
{
    protected $table='active';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
