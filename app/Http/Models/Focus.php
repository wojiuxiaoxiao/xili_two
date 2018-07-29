<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Focus extends Model
{
    protected $table='focus';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
