<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class InterlocutionBounty extends Model
{
    protected $table='interlocution_bounty';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
