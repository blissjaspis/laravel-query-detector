<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() : void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('bio');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('author_id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('author_id');
            $table->date('birthday');
            $table->string('city');
            $table->string('state');
            $table->string('website');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('body');
            $table->morphs('commentable');
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('authors');
    }
};
