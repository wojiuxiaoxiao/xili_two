<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CommentActive extends Model
{
    protected $table='comment_active';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
