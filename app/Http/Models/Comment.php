<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table='comment';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];

    public function programV1()
    {
        return $this->hasOne('App\Http\Models\Program', 'id','program_id');
    }

    /**
     * 获取关联到用户的签约作者
     */
    public function FeedBack()
    {
        return $this->hasOne('App\Http\Models\FeedBack', 'id','id');
    }

}
