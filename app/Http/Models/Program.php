<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $table='program';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
