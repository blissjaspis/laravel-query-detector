<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\ProfileFactory;

class Profile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory()
    {
        return ProfileFactory::new();
    }
}
