<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table='client';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
