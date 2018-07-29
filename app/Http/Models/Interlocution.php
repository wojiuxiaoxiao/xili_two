<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Interlocution extends Model
{
    protected $table='interlocution';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
