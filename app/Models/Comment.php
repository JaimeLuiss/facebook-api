<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = ['post_id', 'author', 'content'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}