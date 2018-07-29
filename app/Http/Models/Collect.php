<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Collect extends Model
{
    protected $table='collect';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
