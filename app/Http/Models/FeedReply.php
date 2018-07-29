<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class FeedReply extends Model
{
    protected $table='feed_reply';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
