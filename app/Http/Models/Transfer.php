<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $table='transfer';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
