<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class SolarTerms extends Model
{
    protected $table='solar_terms';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
