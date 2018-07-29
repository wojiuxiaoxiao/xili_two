<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Attention extends Model
{
    protected $table='attention';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
