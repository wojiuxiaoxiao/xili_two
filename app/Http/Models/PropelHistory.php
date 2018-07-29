<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PropelHistory extends Model
{
    protected $table='propel_history';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
