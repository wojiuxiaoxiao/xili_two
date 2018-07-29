<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CollectMulti extends Model
{
    protected $table='collect_multi';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
