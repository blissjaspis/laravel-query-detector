<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Workbench\App\Models\Author;
use Workbench\App\Models\Post;

class QueryController
{
    public function nPlusQuery()
    {
        $authors = Author::all();

        foreach ($authors as $author) {
            $author->profile;
        }
    }

    public function notNPlusQuery()
    {
        $authors = Author::with('profile')->get();

        foreach ($authors as $author) {
            $author->profile;
        }
    }

    public function nPlusQueryFromBuilder()
    {
        $authors = Author::with('profile')->get();

        foreach ($authors as $author) {
            $author->profile;
            $author->posts()->where(1)->get();
        }
    }

    public function detectAllNPlusQuery()
    {
        $authors = Author::with('profile')->get();

        foreach ($authors as $author) {
            $author->profile;
            $author->posts()->where(1)->get();
        }

        foreach (Post::all() as $post) {
            $post->author;
        }
    }

    public function detectNPlusQueryOnMorphRelation()
    {
        foreach (Post::all() as $post) {
            $post->comments;
        }
    }

    public function detectNPlusQueryOnMorphRelationWithBuilder()
    {
        foreach (Post::all() as $post) {
            $post->comments()->get();
        }
    }

    public function nPlusQueryIgnoresRedirects()
    {
        foreach (Post::all() as $post) {
            $post->comments;
        }

        return redirect()->to('/random');
    }

    public function fireAnEventIfDetectNQuery()
    {
        $authors = Author::all();

        foreach ($authors as $author) {
            $author->profile;
        }
    }

    public function notFireAnEventIfDetectNoNQuery()
    {
        $authors = Author::with('profile')->get();

        foreach ($authors as $author) {
            $author->profile;
        }
    }

    public function useTraceLineToDetectQuery()
    {
        $authors = Author::all();
        $authors2 = Author::all();

        foreach ($authors as $author) {
            $author->profile->city;
        }

        foreach ($authors2 as $author) {
            $author->profile->city;
        }
    }
}
