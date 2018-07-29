<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table='user';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];

    /**
     * 获取关联到用户的签的评论
     */
    public function comment()
    {
        return $this->hasOne('App\Http\Models\Comment', 'user_id');
    }

    /**
     * 获取关联到用户的签的评论
     */
    public function signedAuthor()
    {
        return $this->hasOne('App\Http\Models\SignedAuthor', 'user_id');
        //return $this->belongsTo('App\Http\Models\SignedAuthor', 'user_id');
    }

    /**
     * 获取关联到用户的收藏
     */
    public  function collect(){
        return $this->hasOne('App\Http\Models\Collect', 'user_id');
    }

}
