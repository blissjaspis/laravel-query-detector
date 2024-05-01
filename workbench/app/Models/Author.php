<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\AuthorFactory;

class Author extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory()
    {
        return AuthorFactory::new();
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
