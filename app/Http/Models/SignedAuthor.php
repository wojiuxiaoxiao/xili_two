<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class SignedAuthor extends Model
{
    protected $table='signed_author';
    protected $primaryKey='user_id';
    public $timestamps=false;
    protected $guarded=[];

    /**
     * 获取关联到用户的签的评论
     */
    public function signedAuthor1()
    {
        return $this->belongsTo('App\Http\Models\User', 'id');
    }
}
