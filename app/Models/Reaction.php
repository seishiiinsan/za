<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Like d'un alter sur un post. Le pivot est aussi manipulé via `Post::reactions()`. */
class Reaction extends Model
{
    protected $fillable = ['post_id', 'alter_id'];
}
