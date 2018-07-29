<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Column extends Model
{
    protected $table='column';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
