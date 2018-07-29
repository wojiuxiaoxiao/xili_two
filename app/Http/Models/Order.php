<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $table = 'order';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
