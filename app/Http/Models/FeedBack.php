<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class FeedBack extends Model
{
    protected $table='feed_back';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
