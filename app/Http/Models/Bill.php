<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $table='bill';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
