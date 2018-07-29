<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table='request';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
