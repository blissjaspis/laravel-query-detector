<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Models\Author;

Route::get('/', function () {
    $authors = Author::all();

    foreach ($authors as $author) {
        $author->profile;
    }

    return response('masuk');
});
