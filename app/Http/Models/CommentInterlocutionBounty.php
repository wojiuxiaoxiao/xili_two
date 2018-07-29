<?php
/**
 * Created by PhpStorm.
 * User: Aaron
 * Date: 2018/6/26
 * Time: 12:01
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class CommentInterlocutionBounty extends Model
{
    public $table = 'comment_interlocution_bounty';
    protected $primaryKey='id';
    public $timestamps=false;
    protected $guarded=[];
}
