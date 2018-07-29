<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table='banner';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
