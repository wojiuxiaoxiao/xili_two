<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CommentInterlocution extends Model
{
    public $table = 'comment_interlocution';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
