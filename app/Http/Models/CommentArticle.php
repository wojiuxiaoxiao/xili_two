<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CommentArticle extends Model
{
    protected $table='comment_article';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
