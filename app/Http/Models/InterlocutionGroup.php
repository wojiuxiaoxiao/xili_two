<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class InterlocutionGroup extends Model
{
    protected $table='interlocution_group';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
