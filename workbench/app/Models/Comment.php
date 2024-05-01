<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\CommentFactory;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory()
    {
        return CommentFactory::new();
    }

    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}
